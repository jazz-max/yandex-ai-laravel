<?php
// Streaming text generation

use JazzMax\YandexAi\Facades\YandexAi;

$response = YandexAi::responses()->stream(
    params: [
        'model'            => 'yandexgpt-5-lite',
        'instructions'     => 'You are a helpful assistant.',
        'input'            => 'Write a short poem about coding.',
        'temperature'      => 0.7,
        'max_output_tokens' => 500,
    ],
    onDelta: function (string $delta) {
        echo $delta; // Print each chunk as it arrives
        flush();
    },
    onComplete: function ($response) {
        echo "\n\nTokens used: {$response->totalTokens()}\n";
    },
);
