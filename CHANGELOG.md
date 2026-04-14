# Changelog

All notable changes to `jazz-max/yandex-ai-laravel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v1.2.0](https://github.com/jazz-max/yandex-ai-laravel/compare/v1.1.2...v1.2.0) - 2026-04-14

### Added
- `FunctionCallFallbackParser` — automatically extracts function calls when Yandex models return them as plain text instead of proper `function_call` output items
- `Response::$isFallbackFunctionCall` property to distinguish fallback-parsed calls from native API responses
- Streaming support for `function_call` SSE events (`response.output_item.added`, `response.function_call_arguments.delta`) in `ResponsesClient::stream()`
- `$onFunctionCall` callback parameter in `ResponsesClient::stream()` for real-time function call notifications
- `FunctionTool::validate()` method — checks tool descriptions for length (>15 words) and non-ASCII characters, logs warnings automatically from `make()`
- `function_call_fallback` config key (`YANDEX_AI_FUNCTION_CALL_FALLBACK` env var, default `true`)

### Changed
- `Response::fromArray()` now accepts optional `$toolNames` parameter for more accurate fallback parsing
- `ResponsesClient::create()` and `stream()` pass tool names through to the Response parser

## [v1.1.2](https://github.com/jazz-max/yandex-ai-laravel/compare/v1.1.1...v1.1.2) - 2026-04-12

### Fixed
- Markdown/MathMarkdown OCR failing with 400 Bad Request

## [v1.1.1](https://github.com/jazz-max/yandex-ai-laravel/compare/v1.1.0...v1.1.1) - 2026-04-12

### Fixed
- SSE stream parsing for Yandex API responses without space after `data:` prefix

## [v1.1.0](https://github.com/jazz-max/yandex-ai-laravel/compare/v1.0.0...v1.1.0) - 2026-04-12

### Added
- Laravel 13 support

## [v1.0.0](https://github.com/jazz-max/yandex-ai-laravel/releases/tag/v1.0.0) - 2026-04-12

### Added
- Initial release
- `ResponsesClient` — text generation via Yandex Responses API (create, stream, continue, background tasks)
- `VisionClient` — sync image OCR and async PDF OCR with polling
- `EmbeddingsClient` — document/query vector embeddings with cosine similarity
- `FunctionTool` helper for building tool definitions with auto-handled empty properties
- `OcrModel` enum with 12 OCR model types and metadata
- Configuration via `YANDEX_AI_API_KEY` and `YANDEX_AI_FOLDER_ID` env vars
- Cost calculation in RUB per 1000 tokens
