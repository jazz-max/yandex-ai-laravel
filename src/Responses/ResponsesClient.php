<?php

namespace JazzMax\YandexAi\Responses;

use Closure;
use GuzzleHttp\Client;
use JazzMax\YandexAi\Exceptions\YandexAiException;

class ResponsesClient
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
     * Format model name as Yandex URI: gpt://folder_id/model_name
     *
     * GOTCHA: Yandex requires this URI format. OpenAI uses plain model names.
     */
    public function formatModel(string $model): string
    {
        if (str_starts_with($model, 'gpt://') || str_starts_with($model, 'emb://')) {
            return $model;
        }

        return "gpt://{$this->folderId}/{$model}";
    }

    /**
     * Strip gpt://folder_id/ prefix for config lookups.
     */
    public function resolveModelName(string $model): string
    {
        if (preg_match('#^gpt://[^/]+/(.+)$#', $model, $m)) {
            return $m[1];
        }

        return $model;
    }

    /**
     * Send a simple request to the Responses API.
     */
    public function create(array $params): Response
    {
        $params['model'] = $this->formatModel($params['model'] ?? config('yandex-ai.default_model'));

        $data = $this->request('POST', '/responses', $params);

        return Response::fromArray($data);
    }

    /**
     * Send a request and stream the response.
     *
     * @param array   $params   Request parameters
     * @param Closure $onDelta  Called with each text delta: fn(string $delta)
     * @param Closure|null $onComplete Called when streaming is complete: fn(Response $response)
     */
    public function stream(array $params, Closure $onDelta, ?Closure $onComplete = null): Response
    {
        $params['model']  = $this->formatModel($params['model'] ?? config('yandex-ai.default_model'));
        $params['stream'] = true;

        $response = $this->http->post("{$this->baseUrl}/responses", [
            'json'    => $params,
            'headers' => $this->headers(),
            'stream'  => true,
        ]);

        $body = $response->getBody();
        $buffer = '';
        $fullText = '';
        $lastEvent = null;

        while (!$body->eof()) {
            $chunk = $body->read(8192);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $line = trim($line);

                // Support both "data: {...}" and "data:{...}" (Yandex API omits the space)
                if (!str_starts_with($line, 'data:')) {
                    continue;
                }

                $json = str_starts_with($line, 'data: ')
                    ? substr($line, 6)
                    : substr($line, 5);

                if ($json === '[DONE]') {
                    break 2;
                }

                $event = json_decode($json, true);
                if (!$event) {
                    continue;
                }

                $lastEvent = $event;

                // Extract text deltas
                $type = $event['type'] ?? '';
                if ($type === 'response.output_text.delta') {
                    $delta = $event['delta'] ?? '';
                    $fullText .= $delta;
                    $onDelta($delta);
                }
            }
        }

        // Build a response object from accumulated data
        $result = Response::fromArray([
            'id'     => $lastEvent['response']['id'] ?? '',
            'model'  => $params['model'],
            'status' => 'completed',
            'output' => [
                [
                    'type'    => 'message',
                    'content' => [['type' => 'output_text', 'text' => $fullText]],
                    'role'    => 'assistant',
                ],
            ],
            'usage' => $lastEvent['response']['usage'] ?? [],
        ]);

        if ($onComplete) {
            $onComplete($result);
        }

        return $result;
    }

    /**
     * Submit a background task.
     */
    public function createBackground(array $params): Response
    {
        $params['background'] = true;

        return $this->create($params);
    }

    /**
     * Retrieve a response by ID (for polling background tasks).
     */
    public function retrieve(string $responseId): Response
    {
        $data = $this->request('GET', "/responses/{$responseId}");

        return Response::fromArray($data);
    }

    /**
     * Poll a background task until completion.
     */
    public function poll(string $responseId, int $timeoutSeconds = 120, int $intervalMs = 2000): Response
    {
        $start = time();

        while (time() - $start < $timeoutSeconds) {
            $response = $this->retrieve($responseId);

            if (in_array($response->status, ['completed', 'failed', 'cancelled'])) {
                return $response;
            }

            usleep($intervalMs * 1000);
        }

        throw new YandexAiException("Background task {$responseId} timed out after {$timeoutSeconds}s");
    }

    /**
     * Continue a conversation using previous_response_id.
     */
    public function continue(string $previousResponseId, array $input, array $extraParams = []): Response
    {
        return $this->create(array_merge($extraParams, [
            'previous_response_id' => $previousResponseId,
            'input'                => $input,
        ]));
    }

    /**
     * Submit a function call result and get the follow-up response.
     */
    public function submitToolOutput(string $previousResponseId, string $callId, string $output, array $extraParams = []): Response
    {
        return $this->continue($previousResponseId, [
            [
                'type'    => 'function_call_output',
                'call_id' => $callId,
                'output'  => $output,
            ],
        ], $extraParams);
    }

    /**
     * Check if a model supports reasoning (Pro models).
     *
     * GOTCHA: Only Pro models (containing "pro" or "5.1") support reasoning.
     * Sending reasoning parameter to non-Pro models may cause errors.
     */
    public function supportsReasoning(string $model): bool
    {
        $name = $this->resolveModelName($model);

        return str_contains($name, 'pro') || str_contains($name, '5.1');
    }

    /**
     * Calculate cost in RUB.
     *
     * GOTCHA: Yandex prices are per 1000 tokens (not per 1M like OpenAI).
     * GOTCHA: Cached tokens are charged at ~50% of prompt price.
     */
    public function calculateCostRub(string $model, int $promptTokens, int $completionTokens, int $cachedTokens = 0): float
    {
        $name   = $this->resolveModelName($model);
        $prices = config("yandex-ai.pricing.{$name}", ['prompt' => 0, 'completion' => 0]);

        $cost  = ($promptTokens / 1000) * $prices['prompt'];
        $cost += ($completionTokens / 1000) * $prices['completion'];
        $cost += ($cachedTokens / 1000) * $prices['prompt'] * 0.5;

        return round($cost, 6);
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $options = ['headers' => $this->headers()];

        if ($body && $method !== 'GET') {
            $options['json'] = $body;
        }

        $response = $this->http->request($method, "{$this->baseUrl}{$path}", $options);
        $data     = json_decode($response->getBody()->getContents(), true);

        if (isset($data['error'])) {
            throw YandexAiException::fromResponse($response->getStatusCode(), $data);
        }

        return $data ?? [];
    }

    /**
     * GOTCHA: Yandex uses "Api-Key" header, NOT "Bearer" like OpenAI.
     * GOTCHA: x-folder-id header is required for all requests.
     */
    private function headers(): array
    {
        return [
            'Authorization' => "Api-Key {$this->apiKey}",
            'x-folder-id'   => $this->folderId,
            'Content-Type'  => 'application/json',
        ];
    }
}
