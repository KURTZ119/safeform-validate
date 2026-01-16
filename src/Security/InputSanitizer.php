<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Security;

/**
 * Input sanitization utility class
 * Provides methods to clean and sanitize user input
 */
class InputSanitizer
{
    /**
     * Sanitize string input - removes HTML tags and trims whitespace
     * @param string $input Raw input string
     * @return string Sanitized string
     */
    public static function sanitizeString(string $input): string
    {
        $sanitized = strip_tags($input);
        $sanitized = trim($sanitized);
        return $sanitized;
    }

    /**
     * Sanitize email address
     * @param string $email Raw email input
     * @return string|false Sanitized email or false if invalid
     */
    public static function sanitizeEmail(string $email): string|false
    {
        $email = trim($email);
        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if ($sanitized === false || !filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        return $sanitized;
    }

    /**
     * Sanitize URL
     * @param string $url Raw URL input
     * @return string|false Sanitized URL or false if invalid
     */
    public static function sanitizeUrl(string $url): string|false
    {
        $url = trim($url);
        $sanitized = filter_var($url, FILTER_SANITIZE_URL);
        
        if ($sanitized === false || !filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        return $sanitized;
    }

    /**
     * Sanitize integer input
     * @param mixed $input Raw input
     * @return int|false Sanitized integer or false if invalid
     */
    public static function sanitizeInt(mixed $input): int|false
    {
        $sanitized = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        
        if ($sanitized === false || !is_numeric($sanitized)) {
            return false;
        }
        
        return (int) $sanitized;
    }

    /**
     * Sanitize float input
     * @param mixed $input Raw input
     * @return float|false Sanitized float or false if invalid
     */
    public static function sanitizeFloat(mixed $input): float|false
    {
        $sanitized = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        if ($sanitized === false || !is_numeric($sanitized)) {
            return false;
        }
        
        return (float) $sanitized;
    }

    /**
     * Escape HTML output to prevent XSS
     * @param string $output Raw output string
     * @return string Escaped HTML string
     */
    public static function escapeHtml(string $output): string
    {
        return htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize filename to prevent directory traversal
     * @param string $filename Raw filename
     * @return string Safe filename
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove directory separators
        $filename = str_replace(['/', '\\', '..'], '', $filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        return $filename;
    }

    /**
     * Sanitize array of inputs
     * @param array<string, mixed> $inputs Array of inputs to sanitize
     * @param string $method Sanitization method to apply
     * @return array<string, mixed> Sanitized array
     */
    public static function sanitizeArray(array $inputs, string $method = 'sanitizeString'): array
    {
        $sanitized = [];
        
        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $method);
            } elseif (is_string($value) && method_exists(self::class, $method)) {
                $sanitized[$key] = self::$method($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Remove null bytes from string (prevents null byte injection)
     * @param string $input Raw input
     * @return string Cleaned string
     */
    public static function removeNullBytes(string $input): string
    {
        return str_replace("\0", '', $input);
    }

    /**
     * Limit string length to prevent DoS attacks
     * @param string $input Raw input
     * @param int $maxLength Maximum allowed length
     * @return string Truncated string
     */
    public static function limitLength(string $input, int $maxLength = 1000): string
    {
        if (mb_strlen($input) > $maxLength) {
            return mb_substr($input, 0, $maxLength);
        }
        
        return $input;
    }
}