<?php
// Image analysis with GPT Vision (gemma-3-27b-it)
//
// GOTCHA: Only gemma-3-27b-it supports images in Yandex AI Studio.
// GOTCHA: Images MUST be base64-embedded, URL references are NOT supported.

use JazzMax\YandexAi\Facades\YandexAi;

$imageData = file_get_contents('photo.jpg');
$base64    = 'data:image/jpeg;base64,' . base64_encode($imageData);

$response = YandexAi::responses()->create([
    'model'            => 'gemma-3-27b-it', // ONLY multimodal model
    'instructions'     => 'You are a vision assistant.',
    'input'            => [
        [
            'role'    => 'user',
            'content' => [
                ['type' => 'input_image', 'image_url' => $base64],
                ['type' => 'input_text',  'text' => 'What is in this image?'],
            ],
        ],
    ],
    'temperature'      => 0.4,
    'max_output_tokens' => 1000,
]);

echo $response->text; // "The image shows..."
