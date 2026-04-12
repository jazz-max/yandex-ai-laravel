<?php
// Simple text generation request

use JazzMax\YandexAi\Facades\YandexAi;

$response = YandexAi::responses()->create([
    'model'            => 'yandexgpt-5-lite',  // SDK auto-formats to gpt://folder_id/...
    'instructions'     => 'You are a helpful assistant.',
    'input'            => 'What is the capital of France?',
    'temperature'      => 0.4,
    'max_output_tokens' => 500,
]);

echo $response->text;                   // "The capital of France is Paris."
echo $response->totalTokens();          // 42
echo $response->usage['cached_tokens']; // 0
