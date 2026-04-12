<?php
// Background (async) text generation with polling

use JazzMax\YandexAi\Facades\YandexAi;

$client = YandexAi::responses();

// Submit background task
$response = $client->createBackground([
    'model'            => 'yandexgpt-5-pro',
    'instructions'     => 'You are a writer.',
    'input'            => 'Write a 1000-word essay about AI.',
    'temperature'      => 0.7,
    'max_output_tokens' => 2000,
]);

echo "Task ID: {$response->id}\n";
echo "Status: {$response->status}\n"; // "in_progress"

// Poll until complete
$result = $client->poll($response->id, timeoutSeconds: 120);

echo "Status: {$result->status}\n"; // "completed"
echo $result->text;
