<?php

namespace JazzMax\YandexAi\Vision;

use GuzzleHttp\Client;
use JazzMax\YandexAi\Enums\OcrModel;
use JazzMax\YandexAi\Exceptions\YandexAiException;

class VisionClient
{
    private Client $http;
    private string $apiKey;
    private string $folderId;
    private string $baseUrl;

    public function __construct(Client $http, string $apiKey, string $folderId, string $baseUrl)
    {
        $this->http     = $http;
        $this->apiKey   = $apiKey;
        $this->folderId = $folderId;
        $this->baseUrl  = rtrim($baseUrl, '/');
    }

    /**
     * Synchronous OCR — recognize a single image.
     *
     * @param string   $imageData Raw binary image data (JPEG, PNG) or PDF
     * @param OcrModel $model     OCR model to use
     */
    public function recognizeText(string $imageData, OcrModel $model = OcrModel::Page): OcrResult
    {
        $mimeType = $this->detectMimeType($imageData);

        $response = $this->http->post("{$this->baseUrl}/recognizeText", [
            'json'    => [
                'mimeType'      => $mimeType,
                'languageCodes' => $model->languages(),
                'model'         => $model->value,
                'content'       => base64_encode($imageData),
            ],
            'headers' => $this->headers(),
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200) {
            throw new YandexAiException(
                "Yandex OCR: HTTP {$response->getStatusCode()} for model {$model->value}",
                $response->getStatusCode(),
                $data,
            );
        }

        $annotation = $data['result']['textAnnotation'] ?? [];

        return new OcrResult(
            fullText: $annotation['fullText'] ?? '',
            markdown: $annotation['markdown'] ?? null,
            entities: $annotation['entities'] ?? [],
            model:    $model,
            pages:    1,
        );
    }

    /**
     * Asynchronous OCR — for multi-page PDFs.
     *
     * GOTCHA: Response is JSON Lines format (one JSON per line, one per page).
     * GOTCHA: Polling interval 2s, timeout 120s recommended.
     *
     * @param string   $pdfData Raw PDF binary data
     * @param OcrModel $model   OCR model
     * @param int      $timeoutSeconds Maximum wait time
     */
    public function recognizeDocument(string $pdfData, OcrModel $model = OcrModel::Page, int $timeoutSeconds = 120): OcrResult
    {
        // Submit async task
        $response = $this->http->post("{$this->baseUrl}/recognizeTextAsync", [
            'json'    => [
                'mimeType'      => 'application/pdf',
                'languageCodes' => $model->languages(),
                'model'         => $model->value,
                'content'       => base64_encode($pdfData),
            ],
            'headers' => $this->headers(),
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $operationId = $data['id'] ?? null;

        if (!$operationId) {
            throw new YandexAiException('Yandex OCR: no operation ID in async response', 0, $data);
        }

        // Poll for result
        return $this->pollResult($operationId, $model, $timeoutSeconds);
    }

    /**
     * Poll async OCR result.
     */
    private function pollResult(string $operationId, OcrModel $model, int $timeoutSeconds): OcrResult
    {
        $start = time();

        while (time() - $start < $timeoutSeconds) {
            sleep(2);

            $response = $this->http->get("{$this->baseUrl}/getRecognition", [
                'query'   => ['operationId' => $operationId],
                'headers' => $this->headers(),
            ]);

            if ($response->getStatusCode() === 200) {
                return $this->parseAsyncResult($response->getBody()->getContents(), $model);
            }
        }

        throw new YandexAiException("Yandex OCR: async task {$operationId} timed out after {$timeoutSeconds}s");
    }

    /**
     * Parse JSON Lines response from async OCR.
     *
     * GOTCHA: Each line is a separate JSON object (one per page).
     */
    private function parseAsyncResult(string $body, OcrModel $model): OcrResult
    {
        $pages    = 0;
        $fullText = '';
        $entities = [];

        foreach (explode("\n", trim($body)) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $pageData = json_decode($line, true);
            if (!$pageData) {
                continue;
            }

            $annotation = $pageData['result']['textAnnotation'] ?? [];
            $pageText   = $annotation['fullText'] ?? '';

            if ($pageText) {
                $fullText .= ($fullText ? "\n\n" : '') . $pageText;
                $pages++;
            }

            foreach ($annotation['entities'] ?? [] as $entity) {
                $entities[] = $entity;
            }
        }

        return new OcrResult(
            fullText: $fullText,
            markdown: null,
            entities: $entities,
            model:    $model,
            pages:    max(1, $pages),
        );
    }

    /**
     * Detect MIME type from binary data using magic bytes.
     */
    private function detectMimeType(string $data): string
    {
        $header = substr($data, 0, 8);

        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            return 'JPEG';
        }
        if (str_starts_with($header, "\x89PNG")) {
            return 'PNG';
        }
        if (str_starts_with($header, '%PDF')) {
            return 'application/pdf';
        }

        return 'JPEG';
    }

    private function headers(): array
    {
        return [
            'Authorization' => "Api-Key {$this->apiKey}",
            'x-folder-id'   => $this->folderId,
            'Content-Type'  => 'application/json',
        ];
    }
}
