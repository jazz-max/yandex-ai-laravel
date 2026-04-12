# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel SDK for Yandex AI Studio (`jazz-max/yandex-ai-laravel`). Provides three API clients: text generation (Responses API), Vision OCR, and Embeddings. Namespace: `JazzMax\YandexAi\`.

## Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit

# Run a single test
./vendor/bin/phpunit --filter=TestClassName
./vendor/bin/phpunit --filter=testMethodName

# Publish config to a Laravel app
php artisan vendor:publish --tag=yandex-ai-config
```

Note: no tests exist yet (PHPUnit and Testbench are in dev dependencies). No CI/CD, Makefile, or linter configured.

## Architecture

Entry point is `YandexAi` singleton (registered in `YandexAiServiceProvider`), accessed via `YandexAi` facade. It lazy-loads three client classes, each receiving a shared Guzzle HTTP client:

- `YandexAi::responses()` → `Responses\ResponsesClient` — text generation, streaming, function calling, multi-turn dialog, background tasks
- `YandexAi::vision()` → `Vision\VisionClient` — sync image OCR, async PDF OCR with polling
- `YandexAi::embeddings()` → `Embeddings\EmbeddingsClient` — document/query vector embeddings, cosine similarity

DTOs: `Responses\Response` (text, functionCall, usage), `Vision\OcrResult` (fullText, entities, pricing).

Supporting: `Tools\FunctionTool` (builds tool definitions with dummy property workaround), `Enums\OcrModel` (12 OCR models with metadata), `Exceptions\YandexAiException` (status code + response body).

Config key: `yandex-ai`. Env vars: `YANDEX_AI_API_KEY`, `YANDEX_AI_FOLDER_ID`.

## Yandex API Quirks (Critical)

These are non-obvious behaviors that differ from OpenAI and will cause bugs if ignored:

- **Auth header**: `Api-Key`, NOT `Bearer`. Plus `x-folder-id` header required on all requests.
- **Model URI format**: models must be `gpt://folder_id/model_name`, not plain names. `ResponsesClient::formatModel()` handles this.
- **Function calling is fragile**: tool descriptions must be <15 words, in English. Empty `properties` must have a `_unused` dummy field (PHP `stdClass` serializes to `[]` not `{}`). `FunctionTool::make()` handles this.
- **Responses API, not Chat Completions**: uses `instructions` (not `system`), `input` (not `messages`), `max_output_tokens` (not `max_tokens`), `previous_response_id` for multi-turn.
- **Only `gemma-3-27b-it` supports images**, base64 only (no URL references).
- **Pricing is RUB per 1000 tokens** (not USD per 1M). Cached tokens at 50% rate.
- **Async OCR returns JSON Lines** (one JSON object per line), not a JSON array.
- **`handwritten`/`table` OCR models**: only `ru`/`en` languages.
- **Reasoning mode**: only Pro models (`yandexgpt-5-pro`, `yandexgpt-5.1`).

Full details: `docs/GOTCHAS.md`.
