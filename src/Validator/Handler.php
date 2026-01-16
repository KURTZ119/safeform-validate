<?php
/**
 * @author Ibnu Yasir
 * @verse 1.0
 */
declare(strict_types=1);

namespace SFORM\FormValidator\Validator;

class Handler
{
    private static array $errors = []; /** @var array<string, string> */
    private static array $result_validate = []; /** @var array<string, bool> */
    private static bool $is_valid = true; 

    public static function setValidationResults(array $results): void { self::$result_validate = $results ?: []; }
    public static function setValidationResult(bool $valid): void { self::$is_valid = $valid === true; }
    public static function getValidationResults(): array
    {
        return self::$result_validate !== [] ? self::$result_validate : [];
    }
    public static function isValid(mixed $validationResult): bool
    {
        if (is_array($validationResult)) {
            if (array_key_exists("valid", $validationResult)) {
                return (bool) $validationResult["valid"];
            }
        }

        return self::$is_valid === true;
    }

    public static function addError(string $key, string $message): void
    {
        self::$errors[$key] = $message;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION["validation_errors"])) {
            $_SESSION["validation_errors"] = [];
        }

        $_SESSION["validation_errors"][$key] = $message;

        if (!array_key_exists($key, $GLOBALS)) {
            $GLOBALS[$key] = $message;
        }
    }

    public static function hasError(string $key): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!empty($_SESSION["validation_errors"]) && isset($_SESSION["validation_errors"][$key])) {
            $GLOBALS[$key] ??= $_SESSION["validation_errors"][$key];
            return true;
        }     
        return array_key_exists($key, self::$errors);
    }

    public static function getError(string $key): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION["validation_errors"][$key])) {
            return $_SESSION["validation_errors"][$key];
        }

        return self::$errors[$key] ?? null;
    }

    /**
     * Get error with HTML wrapper - returns empty string if no error
     * @param string $key Error key
     * @param string $wrapper HTML wrapper with {error} placeholder (default: '<div class="error">{error}</div>')
     * @return string Wrapped error message or empty string
     */
    public static function showError(string $key, string $wrapper = '<div class="error">{error}</div>'): string
    {
        $error = self::getError($key);
        if ($error === null) {
            return '';
        }
        
        $safeError = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
        return str_replace('{error}', $safeError, $wrapper);
    }

    public static function getAllErrors(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $stored = $_SESSION["validation_errors"] ?? [];

        if ($stored === []) {
            return self::$errors;
        }

        return $stored + self::$errors;
    }

    public static function clearErrors(): void
    {
        self::$errors = [];
        self::$is_valid = true;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION["validation_errors"])) {
            unset($_SESSION["validation_errors"]);
        }
    }

    /**
     * Reset validation state WITHOUT clearing errors
     * This allows multiple form validations in the same request
     * Errors accumulate across validations until explicitly cleared
     */
    public static function reset(): void
    {
        // DO NOT clear errors here - they should persist across multiple form validations
        // self::$errors = []; // REMOVED
        self::$result_validate = [];
        self::$is_valid = true;
    }
}