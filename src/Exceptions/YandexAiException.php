<?php

namespace JazzMax\YandexAi\Exceptions;

use RuntimeException;

class YandexAiException extends RuntimeException
{
    public function __construct(
        string              $message,
        public readonly int $statusCode = 0,
        public readonly ?array $response = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    public static function fromResponse(int $statusCode, array $body): static
    {
        $message = $body['error']['message']
            ?? $body['error_message']
            ?? $body['message']
            ?? "Yandex AI API error (HTTP {$statusCode})";

        return new static($message, $statusCode, $body);
    }
}
