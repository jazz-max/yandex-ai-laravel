# Yandex AI Laravel SDK

Laravel SDK for [Yandex AI Studio](https://aistudio.yandex.ru/) — text generation (Responses API), Vision OCR, and Embeddings.

## Installation

```bash
composer require jazz-max/yandex-ai-laravel
```

Publish config:
```bash
php artisan vendor:publish --tag=yandex-ai-config
```

Add to `.env`:
```env
YANDEX_AI_API_KEY=your-api-key
YANDEX_AI_FOLDER_ID=your-folder-id
```

## Quick Start

```php
use JazzMax\YandexAi\Facades\YandexAi;

// Text generation
$response = YandexAi::responses()->create([
    'model'        => 'yandexgpt-5-lite',
    'instructions' => 'You are a helpful assistant.',
    'input'        => 'Hello!',
]);
echo $response->text;

// OCR
$result = YandexAi::vision()->recognizeText(
    file_get_contents('photo.jpg')
);
echo $result->text();

// Embeddings
$vector = YandexAi::embeddings()->embedQuery('search query');
```

## API Clients

### Responses API

```php
$client = YandexAi::responses();
```

| Method | Description |
|--------|-------------|
| `create(array $params)` | Text generation |
| `stream(array $params, Closure $onDelta)` | Streaming generation |
| `continue(string $prevId, array $input)` | Multi-turn dialog |
| `submitToolOutput(string $prevId, string $callId, string $output)` | Function calling follow-up |
| `createBackground(array $params)` | Background async task |
| `retrieve(string $id)` | Get background task status |
| `poll(string $id, int $timeout)` | Poll until complete |
| `formatModel(string $model)` | Add `gpt://folder_id/` prefix |
| `supportsReasoning(string $model)` | Check Pro model |
| `calculateCostRub(...)` | Cost in RUB |

### Vision OCR

```php
$client = YandexAi::vision();
```

| Method | Description |
|--------|-------------|
| `recognizeText(string $data, OcrModel $model)` | Sync image OCR |
| `recognizeDocument(string $pdfData, OcrModel $model)` | Async PDF OCR |

Available models (`OcrModel` enum):
- `Page` — general text
- `PageColumnSort` — multi-column layouts
- `Handwritten` — handwriting (ru/en only)
- `Table` — table extraction (ru/en only)
- `Markdown` / `MathMarkdown` — structured output
- `Passport` — Russian passport fields
- `DriverLicenseFront` / `DriverLicenseBack` — driver license
- `VehicleRegistrationFront` / `VehicleRegistrationBack` — vehicle registration
- `LicensePlates` — license plate numbers

### Embeddings

```php
$client = YandexAi::embeddings();
```

| Method | Description |
|--------|-------------|
| `embedDocument(string $text)` | Document vector (for indexing) |
| `embedQuery(string $text)` | Query vector (for searching) |
| `cosineSimilarity(array $a, array $b)` | Vector similarity |

## Function Calling

```php
use JazzMax\YandexAi\Tools\FunctionTool;

$tools = [
    FunctionTool::make('get_weather', 'Get weather for a city.', [
        'type'       => 'object',
        'properties' => [
            'city' => ['type' => 'string', 'description' => 'City name'],
        ],
        'required' => ['city'],
    ]),
];

$response = YandexAi::responses()->create([
    'model' => 'yandexgpt-5-lite',
    'input' => 'Weather in Moscow?',
    'tools' => $tools,
]);

if ($response->hasFunctionCall()) {
    $call = $response->functionCall;
    // Execute function, then submit result:
    $final = YandexAi::responses()->submitToolOutput(
        $response->id, $call['id'], '{"temp": 15}'
    );
}
```

## Known Gotchas

See [docs/GOTCHAS.md](docs/GOTCHAS.md) for production-tested pitfalls:

1. **Tool descriptions must be short** (< 15 words, English) or models write calls as text
2. **Never use empty `properties`** — always include a dummy property
3. **Auth header is `Api-Key`**, not `Bearer`
4. **Models require URI format**: `gpt://folder_id/model`
5. **Only `gemma-3-27b-it` supports images**, and only via base64
6. **Prices are RUB per 1000 tokens**, not USD per 1M
7. **Cached tokens cost 50%** of prompt price
8. **Async OCR returns JSON Lines**, not JSON array
9. **`handwritten`/`table` models only support ru/en**

## Configuration

All options in `config/yandex-ai.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `api_key` | — | API key |
| `folder_id` | — | Cloud folder ID |
| `base_url` | `https://ai.api.cloud.yandex.net/v1` | API base URL |
| `ocr_base_url` | `https://ocr.api.cloud.yandex.net/ocr/v1` | OCR API URL |
| `timeout` | `120` | HTTP timeout (seconds) |
| `proxy` | `null` | HTTP proxy URL |
| `default_model` | `yandexgpt-5-lite` | Default generation model |
| `pricing` | — | RUB per 1000 tokens per model |
| `ocr_pricing` | — | RUB per image per OCR model |

## Examples

See [examples/](examples/) directory:
- `simple_request.php` — basic generation
- `dialog.php` — multi-turn conversation
- `streaming.php` — streaming output
- `function_calling.php` — tool use
- `reasoning.php` — Pro model reasoning
- `vision_with_gpt.php` — image analysis
- `vision_ocr.php` — OCR recognition
- `vision_ocr_async.php` — async PDF OCR
- `embeddings.php` — semantic search
- `background.php` — background tasks

## Claude Code Skill

If you use [Claude Code](https://claude.ai/code), install the companion skill for expert guidance on this SDK:

```bash
npx skills add jazz-max/yandex-ai-laravel-skill
```

The skill provides full API reference, code examples, and knows all Yandex API gotchas.

Example prompts that activate the skill:

> Add YandexGPT text generation to my Laravel app with streaming support

> Build a document recognition feature using Yandex Vision OCR for passports and driver licenses

> Implement semantic search with Yandex Embeddings and cosine similarity

> Set up function calling with Yandex AI Studio in my Laravel project

> Подключи Yandex AI Studio к моему Laravel-проекту и сделай artisan-команду для генерации текста через YandexGPT

> Добавь распознавание документов через Yandex Vision OCR с поддержкой паспортов и водительских удостоверений

> Реализуй семантический поиск по базе статей с помощью Yandex Embeddings

## License

MIT
