<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Unicode;

/**
 * method for validating, converting, and detecting
 * character encodings with support for multiple encoding types.
 */
class CharUnicode
{
    /**
     * @param string $string String to check
     * @param string $encoding Encoding type (UTF-8, ISO-8859-1, etc.)
     * @return bool True if valid, false otherwise
     */
    public static function isValidEncoding(string $string, string $encoding = "UTF-8"): bool
    {
        $encoding = strtoupper($encoding);
        if (!in_array($encoding, mb_list_encodings(), true)) {
            return false;
        }
        return mb_check_encoding($string, $encoding);
    }

    /**
     * @param string $string String to convert
     * @param string $toEncoding Target encoding
     * @param string $fromEncoding Source encoding
     * @return string|false Converted string or false on failure
     */
    public static function convert(string $string, string $toEncoding, string $fromEncoding = 'auto'): string|false
    {
        return mb_convert_encoding($string, $toEncoding, $fromEncoding);
    }

    /**
     * @param string $string String to detect
     * @return string|false Detected encoding or false on failure
     */
    public static function detectEncoding(string $string): string|false
    {
        return mb_detect_encoding($string, mb_detect_order(), true);
    }
    /**
     * Get string length in specified encoding
     */
    public static function length(string $string, string $encoding = "UTF-8"): int
    {
        return mb_strlen($string, $encoding);
    }
    public static function sanitize(string $string, string $encoding = "UTF-8"): string
    {
        $result = mb_convert_encoding($string, $encoding, $encoding);
        return $result !== false ? $result : $string;
    }
    public static function isValidUtf8(string $string): bool
    {
        return self::isValidEncoding($string, "UTF-8");
    }

    /**
     * @return array<int, string> Array of supported encoding names
     */
    public static function getSupportedEncodings(): array
    {
        return mb_list_encodings();
    }
}
