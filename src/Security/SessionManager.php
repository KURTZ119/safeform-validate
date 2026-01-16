<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Security;

/**
 * Secure session management utility
 * Provides methods for secure session handling
 */
class SessionManager
{
    private static bool $initialized = false;
    private static array $config = [
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'sid_length' => 48,
        'sid_bits_per_character' => 6,
    ];

    /**
     * Initialize secure session with best practices
     * @param array<string, mixed> $customConfig Custom configuration options
     * @return void
     */
    public static function init(array $customConfig = []): void
    {
        if (self::$initialized) {
            return;
        }

        // Merge custom config
        $config = array_merge(self::$config, $customConfig);

        // Set session configuration
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', $config['cookie_httponly'] ? '1' : '0');
            ini_set('session.cookie_secure', $config['cookie_secure'] ? '1' : '0');
            ini_set('session.cookie_samesite', $config['cookie_samesite']);
            ini_set('session.use_strict_mode', $config['use_strict_mode'] ? '1' : '0');
            ini_set('session.use_only_cookies', $config['use_only_cookies'] ? '1' : '0');
            ini_set('session.sid_length', (string) $config['sid_length']);
            ini_set('session.sid_bits_per_character', (string) $config['sid_bits_per_character']);

            session_start();
        }

        // Regenerate session ID on first init to prevent session fixation
        if (!isset($_SESSION['_session_initialized'])) {
            session_regenerate_id(true);
            $_SESSION['_session_initialized'] = true;
            $_SESSION['_session_created'] = time();
        }

        // Check session timeout (30 minutes default)
        self::checkTimeout(1800);

        self::$initialized = true;
    }

    /**
     * Start session safely
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            self::init();
        }
    }

    /**
     * Regenerate session ID to prevent session fixation
     * @param bool $deleteOldSession Whether to delete old session data
     * @return void
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
            $_SESSION['_session_regenerated'] = time();
        }
    }

    /**
     * Check session timeout and regenerate if needed
     * @param int $timeout Timeout in seconds
     * @return void
     */
    private static function checkTimeout(int $timeout): void
    {
        if (isset($_SESSION['_session_last_activity'])) {
            $elapsed = time() - $_SESSION['_session_last_activity'];
            
            if ($elapsed > $timeout) {
                self::destroy();
                return;
            }
        }

        $_SESSION['_session_last_activity'] = time();

        // Regenerate session ID every 30 minutes
        if (isset($_SESSION['_session_regenerated'])) {
            if (time() - $_SESSION['_session_regenerated'] > 1800) {
                self::regenerate();
            }
        }
    }

    /**
     * Destroy session securely
     * @return void
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            // Delete session cookie
            if (isset($_COOKIE[session_name()])) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
        }
        
        self::$initialized = false;
    }

    /**
     * Set session value
     * @param string $key Session key
     * @param mixed $value Value to store
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     * @param string $key Session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Session value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     * @param string $key Session key
     * @return bool True if exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     * @param string $key Session key
     * @return void
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Flash data - store for one request
     * @param string $key Flash key
     * @param mixed $value Value to flash
     * @return void
     */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get flash data
     * @param string $key Flash key
     * @param mixed $default Default value
     * @return mixed Flash value or default
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}