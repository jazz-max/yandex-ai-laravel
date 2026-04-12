<?php
// Multi-turn dialog using previous_response_id

use JazzMax\YandexAi\Facades\YandexAi;

$client = YandexAi::responses();

// First message
$response = $client->create([
    'model'        => 'yandexgpt-5-lite',
    'instructions' => 'You are a travel advisor.',
    'input'        => 'I want to visit Japan.',
]);

echo $response->text; // "Japan is a wonderful choice! ..."

// Follow-up — SDK uses previous_response_id automatically
$response2 = $client->continue($response->id, [
    ['role' => 'user', 'content' => 'What about food?'],
]);

echo $response2->text; // "Japanese cuisine includes..."

// Another follow-up
$response3 = $client->continue($response2->id, [
    ['role' => 'user', 'content' => 'Best season to visit?'],
]);

echo $response3->text; // "Spring (March-May) is ideal..."
