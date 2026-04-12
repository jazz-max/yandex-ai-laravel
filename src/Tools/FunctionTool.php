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
