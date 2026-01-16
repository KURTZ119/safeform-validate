<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Form;

use SFORM\FormValidator\Security\Logger;

class Xcsrf
{
    private static string $tokenName  = "_csrf_token";
    private static string $sessionKey = "csrf_tokens";
    private static int $maxTokens = 10;
    private static int $tokenLifetime = 3600;

    /**
     * Initialize session securely
     */
    private static function initSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            // Set secure session parameters
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            
            session_start();
        }

        if (!isset($_SESSION[self::$sessionKey]))
        {
            $_SESSION[self::$sessionKey] = [];
        }
    }

    /**
     * Generate a secure random token using cryptographically secure method
     * @return string 64-character hexadecimal token
     */
    private static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate and output CSRF token as hidden input field
     * @return void
     */
    public static function token(): void
    {
        self::initSession();
        
        $token = self::generateToken();
        $_SESSION[self::$sessionKey][$token] = time();
        
        // Limit number of stored tokens to prevent memory issues
        if (count($_SESSION[self::$sessionKey]) > self::$maxTokens) {
            $_SESSION[self::$sessionKey] = array_slice(
                $_SESSION[self::$sessionKey],
                -self::$maxTokens,
                self::$maxTokens,
                true
            );
        }
        
        echo '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate CSRF token from POST request
     * @return bool True if valid, false otherwise
     */
    public static function validate(): bool
    {
        self::initSession();
        
        // Only validate POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::logSecurityEvent('CSRF validation attempted on non-POST request', [
                'method' => $_SERVER['REQUEST_METHOD']
            ]);
            return false;
        }

        // Check if token exists in POST data
        if (!isset($_POST[self::$tokenName])) {
            Logger::logCsrfFailure(['reason' => 'Token not found in POST data']);
            return false;
        }
        
        $submittedToken = $_POST[self::$tokenName];

        // Validate token format (should be 64 hex characters)
        if (!preg_match('/^[a-f0-9]{64}$/i', $submittedToken)) {
            Logger::logCsrfFailure(['reason' => 'Invalid token format']);
            return false;
        }

        // Check if token exists in session
        if (!isset($_SESSION[self::$sessionKey][$submittedToken])) {
            Logger::logCsrfFailure(['reason' => 'Token not found in session']);
            return false;
        }

        $tokenTime = $_SESSION[self::$sessionKey][$submittedToken];

        // Check if token has expired
        if (time() - $tokenTime > self::$tokenLifetime) {
            Logger::logCsrfFailure(['reason' => 'Token expired']);
            unset($_SESSION[self::$sessionKey][$submittedToken]);
            return false;
        }

        // Token is valid - remove it (one-time use)
        unset($_SESSION[self::$sessionKey][$submittedToken]);
        
        return true;
    }

    /**
     * Set maximum number of tokens to store
     * @param int $max Maximum number of tokens
     * @return void
     */
    public static function setMaxTokens(int $max): void
    {
        self::$maxTokens = max(1, $max);
    }

    /**
     * Set token lifetime in seconds
     * @param int $seconds Token lifetime
     * @return void
     */
    public static function setTokenLifetime(int $seconds): void
    {
        self::$tokenLifetime = max(60, $seconds); // Minimum 1 minute
    }

    /**
     * Clear all CSRF tokens from session
     * @return void
     */
    public static function clearAllTokens(): void
    {
        self::initSession();
        $_SESSION[self::$sessionKey] = [];
    }

    /**
     * Get number of active tokens
     * @return int Number of tokens
     */
    public static function getTokenCount(): int
    {
        self::initSession();
        return count($_SESSION[self::$sessionKey]);
    }
}