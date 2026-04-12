<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    | Yandex AI Studio API key. Get it at https://aistudio.yandex.ru/
    | Yandex uses "Api-Key" header (NOT "Bearer" like OpenAI).
    */
    'api_key' => env('YANDEX_AI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Folder ID
    |--------------------------------------------------------------------------
    | Yandex Cloud folder ID. Required for model URIs (gpt://folder_id/model).
    */
    'folder_id' => env('YANDEX_AI_FOLDER_ID'),

    /*
    |--------------------------------------------------------------------------
    | Base URLs
    |--------------------------------------------------------------------------
    */
    'base_url'     => env('YANDEX_AI_BASE_URL', 'https://ai.api.cloud.yandex.net/v1'),
    'ocr_base_url' => env('YANDEX_AI_OCR_URL', 'https://ocr.api.cloud.yandex.net/ocr/v1'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */
    'timeout'         => (int) env('YANDEX_AI_TIMEOUT', 120),
    'connect_timeout' => (int) env('YANDEX_AI_CONNECT_TIMEOUT', 10),
    'proxy'           => env('YANDEX_AI_PROXY'),
    'verify_ssl'      => (bool) env('YANDEX_AI_VERIFY_SSL', false),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    */
    'default_model' => env('YANDEX_AI_MODEL', 'yandexgpt-5-lite'),

    /*
    |--------------------------------------------------------------------------
    | Pricing (RUB per 1000 tokens)
    |--------------------------------------------------------------------------
    | IMPORTANT: Yandex prices are per 1000 tokens in RUB (not per 1M in USD).
    | Do NOT divide by 1000 again — the SDK handles conversion.
    */
    'pricing' => [
        'yandexgpt-5-lite' => ['prompt' => 0.2,  'completion' => 0.2],
        'yandexgpt-5-pro'  => ['prompt' => 1.2,  'completion' => 1.2],
        'yandexgpt-5.1'    => ['prompt' => 0.8,  'completion' => 0.8],
        'aliceai-llm'      => ['prompt' => 0.5,  'completion' => 1.2],
        'gemma-3-27b-it'   => ['prompt' => 0.4,  'completion' => 0.4],
    ],

    /*
    |--------------------------------------------------------------------------
    | OCR Pricing (RUB per image)
    |--------------------------------------------------------------------------
    */
    'ocr_pricing' => [
        'page'                       => 0.132,
        'page-column-sort'           => 0.132,
        'handwritten'                => 1.527,
        'table'                      => 1.22,
        'markdown'                   => 0.132,
        'math-markdown'              => 0.132,
        'passport'                   => 0.712,
        'driver-license-front'       => 0.71,
        'driver-license-back'        => 0.71,
        'vehicle-registration-front' => 0.71,
        'vehicle-registration-back'  => 0.71,
        'license-plates'             => 0.132,
    ],
];
