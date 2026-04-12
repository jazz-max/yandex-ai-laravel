<?php
// Function calling (tool use)
//
// IMPORTANT: Keep tool descriptions SHORT (< 15 words, English).
// Long descriptions cause Yandex models to write tool calls as text.

use JazzMax\YandexAi\Facades\YandexAi;
use JazzMax\YandexAi\Tools\FunctionTool;

$client = YandexAi::responses();

// Define tools
$tools = [
    FunctionTool::make('get_weather', 'Get current weather for a city.', [
        'type'       => 'object',
        'properties' => [
            'city' => ['type' => 'string', 'description' => 'City name'],
        ],
        'required' => ['city'],
    ]),
    FunctionTool::make('get_time', 'Get current time for a timezone.', [
        'type'       => 'object',
        'properties' => [
            'timezone' => ['type' => 'string', 'description' => 'Timezone, e.g. Europe/Moscow'],
        ],
        'required' => ['timezone'],
    ]),
];

// Send request
$response = $client->create([
    'model'            => 'yandexgpt-5-lite',
    'instructions'     => 'You are a helpful assistant with tools.',
    'input'            => 'What is the weather in Moscow?',
    'tools'            => $tools,
    'temperature'      => 0.3,
    'max_output_tokens' => 500,
]);

// Check if model wants to call a function
if ($response->hasFunctionCall()) {
    $call = $response->functionCall;
    echo "Tool: {$call['name']}\n";         // "get_weather"
    echo "Args: " . json_encode($call['arguments']) . "\n"; // {"city":"Moscow"}

    // Execute the function (your logic)
    $result = '{"temperature": 15, "condition": "cloudy"}';

    // Submit result back
    $finalResponse = $client->submitToolOutput(
        previousResponseId: $response->id,
        callId:             $call['id'],
        output:             $result,
    );

    echo $finalResponse->text; // "The weather in Moscow is 15°C and cloudy."
} else {
    echo $response->text;
}
