<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Security;

/**
 * Validates redirect URLs to prevent open redirect vulnerabilities
 */
class RedirectValidator
{
    private static array $allowedHosts = [];
    private static bool $allowRelativeUrls = true;

    /**
     * Set allowed hosts for redirects
     * @param array<string> $hosts Array of allowed hostnames
     * @return void
     */
    public static function setAllowedHosts(array $hosts): void
    {
        self::$allowedHosts = $hosts;
    }

    /**
     * Set whether relative URLs are allowed
     * @param bool $allow True to allow relative URLs
     * @return void
     */
    public static function setAllowRelativeUrls(bool $allow): void
    {
        self::$allowRelativeUrls = $allow;
    }

    /**
     * Validate if a URL is safe for redirect
     * @param string $url URL to validate
     * @return bool True if safe, false otherwise
     */
    public static function isValidRedirectUrl(string $url): bool
    {
        // Remove any whitespace
        $url = trim($url);

        // Check for null bytes
        if (str_contains($url, "\0")) {
            return false;
        }

        // Check for javascript: or data: protocols
        if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return false;
        }

        // Allow relative URLs if configured
        if (self::$allowRelativeUrls && self::isRelativeUrl($url)) {
            return self::isValidRelativeUrl($url);
        }

        // Parse the URL
        $parsed = parse_url($url);

        if ($parsed === false) {
            return false;
        }

        // If no host, treat as relative URL
        if (!isset($parsed['host'])) {
            return self::$allowRelativeUrls && self::isValidRelativeUrl($url);
        }

        // Check if scheme is allowed (only http and https)
        if (isset($parsed['scheme']) && !in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            return false;
        }

        // If allowed hosts are configured, check against them
        if (!empty(self::$allowedHosts)) {
            return self::isHostAllowed($parsed['host']);
        }

        // If no allowed hosts configured, only allow same host
        return self::isSameHost($parsed['host']);
    }

    /**
     * Check if URL is relative
     * @param string $url URL to check
     * @return bool True if relative
     */
    private static function isRelativeUrl(string $url): bool
    {
        // Starts with / but not //
        if (preg_match('#^/[^/]#', $url)) {
            return true;
        }

        // No scheme and no host
        $parsed = parse_url($url);
        return !isset($parsed['scheme']) && !isset($parsed['host']);
    }

    /**
     * Validate relative URL
     * @param string $url Relative URL to validate
     * @return bool True if valid
     */
    private static function isValidRelativeUrl(string $url): bool
    {
        // Check for directory traversal attempts
        if (str_contains($url, '..')) {
            return false;
        }

        // Must start with / or be a simple path
        return preg_match('#^(/|[a-zA-Z0-9_-])#', $url) === 1;
    }

    /**
     * Check if host is in allowed list
     * @param string $host Hostname to check
     * @return bool True if allowed
     */
    private static function isHostAllowed(string $host): bool
    {
        $host = strtolower($host);

        foreach (self::$allowedHosts as $allowedHost) {
            if (strtolower($allowedHost) === $host) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if host is the same as current host
     * @param string $host Hostname to check
     * @return bool True if same host
     */
    private static function isSameHost(string $host): bool
    {
        $currentHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        return strtolower($host) === strtolower($currentHost);
    }

    /**
     * Sanitize and validate redirect URL
     * Returns safe URL or default fallback
     * @param string $url URL to sanitize
     * @param string $fallback Fallback URL if validation fails
     * @return string Safe URL
     */
    public static function sanitizeRedirectUrl(string $url, string $fallback = '/'): string
    {
        if (self::isValidRedirectUrl($url)) {
            return $url;
        }

        return $fallback;
    }
}