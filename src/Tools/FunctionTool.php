<?php

namespace JazzMax\YandexAi\Tools;

class FunctionTool
{
    /**
     * Build a function tool definition for the Responses API.
     *
     * GOTCHA: Keep descriptions SHORT (< 15 words, English).
     * Long or Russian descriptions cause Yandex models to write tool calls
     * as text ("[tool_name]{}") instead of proper function_call.
     *
     * GOTCHA: Never use empty properties (stdClass or []).
     * PHP serializes stdClass as [] (array) instead of {} (object).
     * Yandex API rejects tools with "properties": [].
     * Always include at least a dummy "_unused" property.
     */
    public static function make(string $name, string $description, array $parameters = []): array
    {
        // Log warnings for tool definitions likely to cause text-format responses
        foreach (static::validate($name, $description, $parameters) as $warning) {
            if (function_exists('logger')) {
                logger()->warning($warning);
            }
        }

        if (empty($parameters)) {
            $parameters = [
                'type'       => 'object',
                'properties' => [
                    '_unused' => [
                        'type'        => 'string',
                        'description' => 'Not used',
                    ],
                ],
            ];
        }

        return [
            'type'        => 'function',
            'name'        => $name,
            'description' => $description,
            'parameters'  => $parameters,
        ];
    }

    /**
     * Validate a tool definition and return warnings.
     *
     * Checks for common issues that cause Yandex models to return
     * function calls as text instead of proper function_call items.
     *
     * @return string[] Array of warning messages (empty if OK)
     */
    public static function validate(string $name, string $description, array $parameters = []): array
    {
        $warnings = [];

        $wordCount = str_word_count($description);
        if ($wordCount > 15) {
            $warnings[] = "Tool '{$name}': description has {$wordCount} words (recommended < 15). Long descriptions cause Yandex models to return function calls as text.";
        }

        if (preg_match('/[^\x00-\x7F]/', $description)) {
            $warnings[] = "Tool '{$name}': description contains non-ASCII characters. Use English descriptions for reliable function calling.";
        }

        if (isset($parameters['properties']) && empty($parameters['properties'])) {
            $warnings[] = "Tool '{$name}': empty properties array. Use FunctionTool::make() to auto-add dummy property.";
        }

        return $warnings;
    }

    /**
     * Build a function_call_output item for follow-up requests.
     */
    public static function output(string $callId, string $output): array
    {
        return [
            'type'    => 'function_call_output',
            'call_id' => $callId,
            'output'  => $output,
        ];
    }
}
