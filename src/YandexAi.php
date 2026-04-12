<?php

namespace JazzMax\YandexAi;

use GuzzleHttp\Client;
use JazzMax\YandexAi\Embeddings\EmbeddingsClient;
use JazzMax\YandexAi\Responses\ResponsesClient;
use JazzMax\YandexAi\Vision\VisionClient;

class YandexAi
{
    private ?ResponsesClient  $responses  = null;
    private ?VisionClient     $vision     = null;
    private ?EmbeddingsClient $embeddings = null;
    private Client            $http;
    private string            $apiKey;
    private string            $folderId;

    public function __construct()
    {
        $this->apiKey   = config('yandex-ai.api_key');
        $this->folderId = config('yandex-ai.folder_id');

        $httpOptions = [
            'timeout'         => config('yandex-ai.timeout', 120),
            'connect_timeout' => config('yandex-ai.connect_timeout', 10),
            'verify'          => config('yandex-ai.verify_ssl', false),
        ];

        $proxy = config('yandex-ai.proxy');
        if ($proxy) {
            $httpOptions['proxy'] = $proxy;
        }

        $this->http = new Client($httpOptions);
    }

    /**
     * Responses API client (text generation, function calling, dialog).
     */
    public function responses(): ResponsesClient
    {
        return $this->responses ??= new ResponsesClient(
            $this->http,
            $this->apiKey,
            $this->folderId,
            config('yandex-ai.base_url'),
        );
    }

    /**
     * Vision OCR client (image/document text recognition).
     */
    public function vision(): VisionClient
    {
        return $this->vision ??= new VisionClient(
            $this->http,
            $this->apiKey,
            $this->folderId,
            config('yandex-ai.ocr_base_url'),
        );
    }

    /**
     * Embeddings client (vector embeddings for search).
     */
    public function embeddings(): EmbeddingsClient
    {
        return $this->embeddings ??= new EmbeddingsClient(
            $this->http,
            $this->apiKey,
            $this->folderId,
            config('yandex-ai.base_url'),
        );
    }
}
