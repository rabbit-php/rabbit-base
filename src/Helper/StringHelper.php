<?php

declare(strict_types=1);

namespace Rabbit\Base\Helper;

/**
 * Class StringHelper
 * @package Rabbit\Base\Helper
 */
class StringHelper
{
    /**
     * @param string $string
     * @return int
     */
    public static function byteLength(string $string): int
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @return bool|string
     */
    public static function byteSubstr(string $string, int $start, int $length = null): string
    {
        return mb_substr($string, $start, $length === null ? mb_strlen($string, '8bit') : $length, '8bit');
    }

    /**
     * @param string $pattern
     * @param string $string
     * @param array $options
     * @return bool
     */
    public static function matchWildcard(string $pattern, string $string, array $options = []): bool
    {
        if ($pattern === '*' && empty($options['filePath'])) {
            return true;
        }

        $replacements = [
            '\\\\\\\\' => '\\\\',
            '\\\\\\*' => '[*]',
            '\\\\\\?' => '[?]',
            '\*' => '.*',
            '\?' => '.',
            '\[\!' => '[^',
            '\[' => '[',
            '\]' => ']',
            '\-' => '-',
        ];

        if (isset($options['escape']) && !$options['escape']) {
            unset($replacements['\\\\\\\\']);
            unset($replacements['\\\\\\*']);
            unset($replacements['\\\\\\?']);
        }

        if (!empty($options['filePath'])) {
            $replacements['\*'] = '[^/\\\\]*';
            $replacements['\?'] = '[^/\\\\]';
        }

        $pattern = strtr(preg_quote($pattern, '#'), $replacements);
        $pattern = '#^' . $pattern . '$#us';

        if (isset($options['caseSensitive']) && !$options['caseSensitive']) {
            $pattern .= 'i';
        }

        return preg_match($pattern, $string) === 1;
    }

    public static function floatToString(float $number): string
    {
        // . and , are the only decimal separators known in ICU data,
        // so its safe to call str_replace here
        return str_replace(',', '.', (string)$number);
    }

    public static function str_n_pos(string $str, string $find, int $n): int
    {
        $pos_val = 0;
        for ($i = 1; $i <= $n; $i++) {
            $pos = strpos($str, $find);
            $str = substr($str, $pos + 1);
            $pos_val = $pos + $pos_val + 1;
        }
        return $pos_val - 1;
    }

    public static function basename(string $path, string $suffix = ''): string
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) === $suffix) {
            $path = mb_substr($path, 0, -$len);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        if (($pos = mb_strrpos($path, '/')) !== false) {
            return mb_substr($path, $pos + 1);
        }

        return $path;
    }

    public static function substitute(string $message, array $params): string
    {
        $placeholders = [];
        foreach ($params as $name => $value) {
            $placeholders['{' . $name . '}'] = $value;
        }

        return empty($placeholders) ? $message : strtr($message, $placeholders);
    }

    public static function mb_ucwords(string $string, string $encoding = null): string
    {
        $words = preg_split("/\s/u", $string, -1, PREG_SPLIT_NO_EMPTY);

        $titelized = array_map(function (string $word) use ($encoding): string {
            return static::mb_ucfirst($word, $encoding);
        }, $words);

        return implode(' ', $titelized);
    }

    public static function mb_ucfirst(string $string, string $encoding = null): string
    {
        $firstChar = static::mb_substr($string, 0, 1, $encoding);
        $rest = static::mb_substr($string, 1, null, $encoding);

        return static::mb_strtoupper($firstChar, $encoding) . $rest;
    }

    public static function mb_substr(string $string, int $start, int $length = null, string $encoding = null): string
    {
        return empty($encoding) ? \mb_substr($string, $start, $length) : \mb_substr(
            $string,
            $start,
            $length,
            $encoding
        );
    }

    public static function mb_strtolower(string $string, string $encoding = null): string
    {
        return empty($encoding) ? \mb_strtolower($string) : \mb_strtolower($string, $encoding);
    }

    public static function mb_strtoupper(string $string, string $encoding = null): string
    {
        return empty($encoding) ? \mb_strtoupper($string) : \mb_strtoupper($string, $encoding);
    }

    public static function NumNormalize($value): string
    {
        if (!is_scalar($value)) {
            $type = gettype($value);
            throw new \InvalidArgumentException("Value must be scalar. $type given.");
        }
        $value = str_replace([' ', ','], ['', '.'], $value);
        return preg_replace('/\.(?=.*\.)/', '', $value);
    }
}
