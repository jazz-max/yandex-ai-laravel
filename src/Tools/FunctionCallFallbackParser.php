<?php

namespace JazzMax\YandexAi\Tools;

/**
 * Fallback parser for function calls returned as plain text.
 *
 * Yandex models (especially yandexgpt-5-pro) sometimes return tool calls
 * as text instead of proper function_call output items. This parser
 * extracts them from text responses.
 *
 * @see docs/GOTCHAS.md section 1
 */
class FunctionCallFallbackParser
{
    /**
     * Try to extract a function call from a text response.
     *
     * @param  string|null $text      The text response from the model
     * @param  string[]    $toolNames Known tool names (improves accuracy, avoids false positives)
     * @return array|null  ['id' => ..., 'name' => ..., 'arguments' => [...]] or null
     */
    public static function tryParse(?string $text, array $toolNames = []): ?array
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $cleaned = self::stripCodeFences(trim($text));

        // If the cleaned text is too long, it's likely a real text response
        // with an incidental match, not a function call
        if (mb_strlen($cleaned) > 500) {
            return null;
        }

        $namePattern = self::buildNamePattern($toolNames);

        // Try patterns in order of specificity
        $result = self::tryBracketJson($cleaned, $namePattern)
            ?? self::tryBareJson($cleaned, $namePattern)
            ?? self::tryPythonStyle($cleaned, $namePattern);

        if ($result === null) {
            return null;
        }

        // Verify that the match covers most of the cleaned text
        // (protects against false positives in longer responses)
        $matchLength = mb_strlen($result['_match'] ?? '');
        $textLength = mb_strlen($cleaned);
        if ($textLength > 0 && $matchLength > 0 && ($matchLength / $textLength) < 0.5) {
            return null;
        }

        unset($result['_match']);

        $result['id'] = 'fallback_' . bin2hex(random_bytes(6));

        return $result;
    }

    /**
     * Strip markdown code fences and backtick wrapping.
     */
    private static function stripCodeFences(string $text): string
    {
        // Triple backtick code fence: ```lang\n...\n```
        if (preg_match('/^```[\w]*\s*\n?(.*?)\n?\s*```$/su', $text, $m)) {
            return trim($m[1]);
        }

        // Single backtick wrapping: `...`
        if (preg_match('/^`([^`]+)`$/su', $text, $m)) {
            return trim($m[1]);
        }

        return $text;
    }

    /**
     * Build a regex fragment for matching tool names.
     */
    private static function buildNamePattern(array $toolNames): string
    {
        if (empty($toolNames)) {
            return '(\w+)';
        }

        $escaped = array_map(fn(string $n) => preg_quote($n, '/'), $toolNames);

        return '(' . implode('|', $escaped) . ')';
    }

    /**
     * Pattern: [tool_name]{"arg":"val"} or [tool_name] {"arg":"val"}
     */
    private static function tryBracketJson(string $text, string $namePattern): ?array
    {
        $pattern = '/^\[' . $namePattern . '\]\s*(\{.*\})$/su';

        if (preg_match($pattern, $text, $m)) {
            $args = self::parseJsonArgs($m[2]);
            if ($args !== null) {
                return [
                    'name'      => $m[1],
                    'arguments' => $args,
                    '_match'    => $m[0],
                ];
            }
        }

        return null;
    }

    /**
     * Pattern: tool_name{"arg":"val"} or tool_name {"arg":"val"}
     */
    private static function tryBareJson(string $text, string $namePattern): ?array
    {
        $pattern = '/^' . $namePattern . '\s*(\{.*\})$/su';

        if (preg_match($pattern, $text, $m)) {
            $args = self::parseJsonArgs($m[2]);
            if ($args !== null) {
                return [
                    'name'      => $m[1],
                    'arguments' => $args,
                    '_match'    => $m[0],
                ];
            }
        }

        return null;
    }

    /**
     * Pattern: tool_name(key="val", key2="val2")
     */
    private static function tryPythonStyle(string $text, string $namePattern): ?array
    {
        $pattern = '/^' . $namePattern . '\s*\(([^)]*)\)$/su';

        if (preg_match($pattern, $text, $m)) {
            $args = self::parsePythonArgs($m[2]);
            if ($args !== null) {
                return [
                    'name'      => $m[1],
                    'arguments' => $args,
                    '_match'    => $m[0],
                ];
            }
        }

        return null;
    }

    /**
     * Parse a JSON arguments string with light repair.
     */
    private static function parseJsonArgs(string $json): ?array
    {
        $json = trim($json);

        // Direct decode
        $result = json_decode($json, true);
        if (is_array($result)) {
            return $result;
        }

        // Repair: single quotes → double quotes
        $repaired = str_replace("'", '"', $json);
        $result = json_decode($repaired, true);
        if (is_array($result)) {
            return $result;
        }

        // Repair: unquoted keys — key: "val" → "key": "val"
        $repaired = preg_replace('/(\{|,)\s*(\w+)\s*:/u', '$1"$2":', $json);
        $result = json_decode($repaired, true);
        if (is_array($result)) {
            return $result;
        }

        return null;
    }

    /**
     * Parse Python-style key=value arguments.
     * e.g. city="Moscow", count=5
     */
    private static function parsePythonArgs(string $argsString): ?array
    {
        $argsString = trim($argsString);
        if ($argsString === '') {
            return [];
        }

        $result = [];
        // Split by commas, respecting quoted strings
        $pairs = preg_split('/,\s*(?=\w+\s*=)/u', $argsString);

        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (!preg_match('/^(\w+)\s*=\s*(.+)$/su', $pair, $m)) {
                return null; // Can't parse — bail out
            }

            $key = $m[1];
            $val = trim($m[2]);

            // Strip surrounding quotes
            if (preg_match('/^(["\'])(.*)\\1$/su', $val, $qm)) {
                $result[$key] = $qm[2];
            } elseif (is_numeric($val)) {
                $result[$key] = $val + 0; // int or float
            } elseif ($val === 'true') {
                $result[$key] = true;
            } elseif ($val === 'false') {
                $result[$key] = false;
            } elseif ($val === 'null') {
                $result[$key] = null;
            } else {
                $result[$key] = $val;
            }
        }

        return $result;
    }
}
