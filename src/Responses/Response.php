<?php

namespace JazzMax\YandexAi\Responses;

use JazzMax\YandexAi\Tools\FunctionCallFallbackParser;

class Response
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $model,
        public readonly string  $status,
        public readonly array   $output,
        public readonly array   $usage,
        public readonly ?string $text,
        public readonly ?array  $functionCall,
        public readonly array   $raw,
        public readonly bool    $isFallbackFunctionCall = false,
    ) {}

    public static function fromArray(array $data, array $toolNames = []): static
    {
        $output = $data['output'] ?? [];
        $text   = null;
        $functionCall = null;
        $isFallbackFunctionCall = false;

        foreach ($output as $item) {
            if (($item['type'] ?? '') === 'function_call') {
                $functionCall = [
                    'id'        => $item['call_id'] ?? $item['id'] ?? '',
                    'name'      => $item['name'] ?? '',
                    'arguments' => json_decode($item['arguments'] ?? '{}', true) ?: [],
                ];
            }

            if (($item['type'] ?? '') === 'message') {
                foreach ($item['content'] ?? [] as $content) {
                    if (($content['type'] ?? '') === 'output_text') {
                        $text = ($text ?? '') . ($content['text'] ?? '');
                    }
                }
            }
        }

        // Also check output_text at top level
        if ($text === null && isset($data['output_text'])) {
            $text = $data['output_text'];
        }

        // Fallback: try to extract function call from text response
        // Yandex models sometimes return tool calls as plain text instead of function_call items
        if ($functionCall === null && $text !== null) {
            try {
                $fallbackEnabled = function_exists('config') && function_exists('app')
                    ? config('yandex-ai.function_call_fallback', true)
                    : true;
            } catch (\Throwable) {
                $fallbackEnabled = true;
            }

            if ($fallbackEnabled) {
                $parsed = FunctionCallFallbackParser::tryParse($text, $toolNames);
                if ($parsed !== null) {
                    $functionCall = $parsed;
                    $isFallbackFunctionCall = true;
                    $text = null;
                }
            }
        }

        $usage = $data['usage'] ?? [];

        return new static(
            id:           $data['id'] ?? '',
            model:        $data['model'] ?? '',
            status:       $data['status'] ?? 'completed',
            output:       $output,
            usage:        [
                'prompt_tokens'     => $usage['input_tokens'] ?? 0,
                'completion_tokens' => $usage['output_tokens'] ?? 0,
                'total_tokens'      => $usage['total_tokens'] ?? 0,
                'cached_tokens'     => $usage['input_tokens_details']['cached_tokens'] ?? 0,
                'reasoning_tokens'  => $usage['output_tokens_details']['reasoning_tokens'] ?? 0,
            ],
            text:         $text,
            functionCall: $functionCall,
            raw:          $data,
            isFallbackFunctionCall: $isFallbackFunctionCall,
        );
    }

    public function hasFunctionCall(): bool
    {
        return $this->functionCall !== null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function promptTokens(): int
    {
        return $this->usage['prompt_tokens'];
    }

    public function completionTokens(): int
    {
        return $this->usage['completion_tokens'];
    }

    public function totalTokens(): int
    {
        return $this->usage['total_tokens'];
    }
}
