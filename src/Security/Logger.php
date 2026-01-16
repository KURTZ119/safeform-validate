<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Security;

/**
 * Security event logger
 * Logs security-related events for audit trails
 */
class Logger
{
    private const LOG_DIR = __DIR__ . '/../../logs';
    private const SECURITY_LOG = 'security.log';
    private const ERROR_LOG = 'error.log';

    /**
     * Log security event
     * @param string $event Event description
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        self::ensureLogDirectory();
        
        $logEntry = self::formatLogEntry('SECURITY', $event, $context);
        $logFile = self::LOG_DIR . '/' . self::SECURITY_LOG;
        
        error_log($logEntry . PHP_EOL, 3, $logFile);
    }

    /**
     * Log validation failure
     * @param string $field Field that failed validation
     * @param string $rule Rule that failed
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function logValidationFailure(string $field, string $rule, array $context = []): void
    {
        $context['field'] = $field;
        $context['rule'] = $rule;
        
        self::logSecurityEvent('Validation failure', $context);
    }

    /**
     * Log CSRF token failure
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function logCsrfFailure(array $context = []): void
    {
        self::logSecurityEvent('CSRF token validation failed', $context);
    }

    /**
     * Log rate limit exceeded
     * @param string $identifier Rate limit identifier
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function logRateLimitExceeded(string $identifier, array $context = []): void
    {
        $context['identifier'] = $identifier;
        self::logSecurityEvent('Rate limit exceeded', $context);
    }

    /**
     * Log suspicious activity
     * @param string $activity Description of suspicious activity
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function logSuspiciousActivity(string $activity, array $context = []): void
    {
        self::logSecurityEvent('Suspicious activity: ' . $activity, $context);
    }

    /**
     * Log error
     * @param string $message Error message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function logError(string $message, array $context = []): void
    {
        self::ensureLogDirectory();
        
        $logEntry = self::formatLogEntry('ERROR', $message, $context);
        $logFile = self::LOG_DIR . '/' . self::ERROR_LOG;
        
        error_log($logEntry . PHP_EOL, 3, $logFile);
    }

    /**
     * Format log entry
     * @param string $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return string Formatted log entry
     */
    private static function formatLogEntry(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        
        return sprintf(
            '[%s] [%s] [IP: %s] %s %s',
            $timestamp,
            $level,
            $ip,
            $message,
            $contextStr
        );
    }

    /**
     * Ensure log directory exists
     * @return void
     */
    private static function ensureLogDirectory(): void
    {
        if (!is_dir(self::LOG_DIR)) {
            mkdir(self::LOG_DIR, 0755, true);
            
            // Create .htaccess to protect logs
            $htaccess = self::LOG_DIR . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Deny from all\n");
            }
        }
    }

    /**
     * Get recent security events
     * @param int $limit Number of events to retrieve
     * @return array<string> Array of log entries
     */
    public static function getRecentSecurityEvents(int $limit = 100): array
    {
        $logFile = self::LOG_DIR . '/' . self::SECURITY_LOG;
        
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines === false) {
            return [];
        }

        return array_slice(array_reverse($lines), 0, $limit);
    }
}