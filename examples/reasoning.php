<?php
// Reasoning mode (Pro models only)

use JazzMax\YandexAi\Facades\YandexAi;

$client = YandexAi::responses();

$response = $client->create([
    'model'            => 'yandexgpt-5-pro',  // or yandexgpt-5.1
    'instructions'     => 'You are a math tutor.',
    'input'            => 'Solve: 2x + 5 = 17',
    'temperature'      => 0.3,
    'max_output_tokens' => 1000,
    'reasoning'        => ['effort' => 'medium'], // low | medium | high
]);

echo $response->text;
echo "Reasoning tokens: {$response->usage['reasoning_tokens']}\n";
echo "Total tokens: {$response->totalTokens()}\n";

// Auto-detect Pro models:
echo $client->supportsReasoning('yandexgpt-5-pro');  // true
echo $client->supportsReasoning('yandexgpt-5-lite'); // false
