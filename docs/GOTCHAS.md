# Known Gotchas & Pitfalls

This document covers issues discovered during production use of Yandex AI Studio APIs.

---

## 1. Function Calling: Model Writes Tool Calls as Text

**Problem:** Instead of returning a proper `function_call` output type, Yandex models (especially `yandexgpt-5-lite`) write the tool call as plain text: `[tool_name]{}` or `resend_file{}`.

**Root Causes:**
- Tool descriptions are too long (> 15 words)
- Tool descriptions are in Russian instead of English
- System prompt is too long or complex (> 250 chars)
- `properties` field is an empty array `[]` instead of empty object `{}`

**Solutions:**
- Keep tool descriptions **short** (< 15 words) and in **English**
- Keep system prompt **short** (~250 chars) and in **English**
- Always include at least one property (use `_unused` dummy property)
- Never use `new \stdClass()` for empty properties — PHP serializes it as `[]`
- Always test with `curl` before deploying (see Testing section)
- Add fallback: parse response text for `[tool_name]` patterns

**Example - BAD:**
```php
// TOO LONG, Russian — model will write as text
'description' => 'Проанализировать последнее загруженное изображение пользователя с помощью AI Vision. ОБЯЗАТЕЛЬНО используй этот tool когда пользователь спрашивает что на изображении.'
```

**Example - GOOD:**
```php
// Short, English — reliable function_call
'description' => 'Analyze user photo with AI Vision.'
```

---

## 2. Empty Properties Serialization

**Problem:** `new \stdClass()` in PHP serializes to `[]` (array) inside nested arrays, not `{}` (object). Yandex API rejects tools with `"properties": []`.

**Solution:** Always use a dummy property:
```php
'parameters' => [
    'type'       => 'object',
    'properties' => [
        '_unused' => ['type' => 'string', 'description' => 'Not used'],
    ],
]
```

Or use `FunctionTool::make('name', 'description')` which handles this automatically.

---

## 3. Authorization Header

**Problem:** Yandex uses `Api-Key` header, NOT `Bearer` like OpenAI.

```php
// WRONG (OpenAI style)
'Authorization' => 'Bearer ' . $apiKey

// CORRECT (Yandex)
'Authorization' => 'Api-Key ' . $apiKey
```

Additionally, `x-folder-id` header is **required** for all requests.

---

## 4. Model URI Format

**Problem:** Yandex requires models as URI: `gpt://folder_id/model_name`. Sending plain model name returns an error.

```php
// WRONG
'model' => 'yandexgpt-5-lite'

// CORRECT
'model' => 'gpt://b1gjrtmjlkdnjeig34pp/yandexgpt-5-lite'
```

The SDK handles this automatically via `formatModel()`.

---

## 5. Responses API vs Chat Completions

**Problem:** Yandex uses the **Responses API** (OpenAI-compatible), not the older Chat Completions API. Parameter names differ:

| OpenAI Chat Completions | Yandex Responses API |
|------------------------|---------------------|
| `system` (in messages) | `instructions` |
| `messages` | `input` |
| `max_tokens` | `max_output_tokens` |
| — | `previous_response_id` |

---

## 6. Vision: Only gemma-3-27b-it

**Problem:** Only `gemma-3-27b-it` supports image input. Other models silently ignore images or return errors.

**Problem:** Images must be base64-embedded. URL references (`image_url: "https://..."`) return: `"image_url is only supported in base64 format"`.

```php
// WRONG
'image_url' => 'https://example.com/photo.jpg'

// CORRECT
'image_url' => 'data:image/jpeg;base64,' . base64_encode($imageData)
```

---

## 7. Pricing: RUB per 1000, NOT per 1M

**Problem:** Yandex prices are per **1000 tokens in RUB**. OpenAI prices are per **1M tokens in USD**. Applying OpenAI-style division results in 1000x underpricing.

```php
// WRONG (OpenAI-style: price / 1000 / 1000)
$cost = ($tokens / 1_000_000) * $price;

// CORRECT (Yandex: price already per 1K)
$cost = ($tokens / 1000) * $price;
```

---

## 8. Cached Tokens Pricing

**Problem:** Yandex charges for `cached_tokens` at ~50% of prompt token price. If you ignore cached tokens, cost tracking is inaccurate.

```php
$cachedCost = ($cachedTokens / 1000) * $promptPrice * 0.5;
```

Cached tokens appear in `usage.input_tokens_details.cached_tokens`.

---

## 9. Async OCR Response Format

**Problem:** `recognizeTextAsync` → `getRecognition` returns **JSON Lines** (one JSON object per line), NOT a single JSON array.

```
{"result":{"textAnnotation":{"fullText":"Page 1..."}}}
{"result":{"textAnnotation":{"fullText":"Page 2..."}}}
```

Must parse line by line:
```php
foreach (explode("\n", $body) as $line) {
    $page = json_decode($line, true);
}
```

---

## 10. OCR Language Limitations

**Problem:** Models `handwritten` and `table` only support `['ru', 'en']`. Sending `['*']` causes recognition errors or empty results.

```php
$languages = in_array($model, ['handwritten', 'table'])
    ? ['ru', 'en']
    : ['*'];
```

---

## 11. Reasoning Mode

**Problem:** Only Pro models (`yandexgpt-5-pro`, `yandexgpt-5.1`) support the `reasoning` parameter. Sending it to lite models may cause errors.

**Problem:** Reasoning tokens are counted in `output_tokens_details.reasoning_tokens` and should be added to completion token cost.

---

## 12. Octane / Swoole Config Caching

**Problem:** When running under Laravel Octane (Swoole), config is cached in worker memory. Changing `.env` requires:
```bash
php artisan config:clear
php artisan octane:reload
```

Queue workers are separate processes — they also need restart:
```bash
php artisan queue:restart
```

---

## Testing Tool Calls

Always test function calling with curl before deploying:

```bash
curl -s https://ai.api.cloud.yandex.net/v1/responses \
  -H "Authorization: Api-Key $API_KEY" \
  -H "x-folder-id: $FOLDER_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gpt://FOLDER_ID/yandexgpt-5-lite",
    "instructions": "You are a helpful assistant with tools.",
    "input": [{"role":"user","content":"do something"}],
    "tools": [YOUR_TOOL_DEFINITION],
    "temperature": 0.3,
    "max_output_tokens": 200
  }'
```

Check that `output[0].type` is `"function_call"`, NOT `"message"`.
