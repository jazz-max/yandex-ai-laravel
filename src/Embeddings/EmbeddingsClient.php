<?php

namespace JazzMax\YandexAi\Embeddings;

use GuzzleHttp\Client;
use JazzMax\YandexAi\Exceptions\YandexAiException;

class EmbeddingsClient
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
     * Generate embeddings for a document (for indexing).
     *
     * Model: text-search-doc/latest
     */
    public function embedDocument(string $text): array
    {
        return $this->embed($text, 'text-search-doc/latest');
    }

    /**
     * Generate embeddings for a query (for searching).
     *
     * Model: text-search-query/latest
     */
    public function embedQuery(string $text): array
    {
        return $this->embed($text, 'text-search-query/latest');
    }

    /**
     * Generate embeddings with a custom model.
     *
     * @return array Vector of floats
     */
    public function embed(string $text, string $model): array
    {
        $modelUri = str_starts_with($model, 'emb://')
            ? $model
            : "emb://{$this->folderId}/{$model}";

        $response = $this->http->post("{$this->baseUrl}/embeddings", [
            'json'    => [
                'model'           => $modelUri,
                'input'           => $text,
                'encoding_format' => 'float',
            ],
            'headers' => [
                'Authorization' => "Api-Key {$this->apiKey}",
                'x-folder-id'   => $this->folderId,
                'Content-Type'  => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['error'])) {
            throw YandexAiException::fromResponse($response->getStatusCode(), $data);
        }

        return $data['data'][0]['embedding'] ?? [];
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $normA      = 0.0;
        $normB      = 0.0;

        for ($i = 0, $len = count($a); $i < $len; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA      += $a[$i] * $a[$i];
            $normB      += $b[$i] * $b[$i];
        }

        $denominator = sqrt($normA) * sqrt($normB);

        return $denominator > 0 ? $dotProduct / $denominator : 0.0;
    }
}
