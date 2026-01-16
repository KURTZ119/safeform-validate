<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Validator\Action;

class Request
{
    private static string $allowedMethod = "POST"; /** Allowed request method */
    public static function setMethod(string $method): void
    {
        self::$allowedMethod = strtoupper($method);
        self::validateMethod();
    }
    private static function validateMethod(): bool
    {
        $currentMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";
        if ($currentMethod !== self::$allowedMethod) {
            http_response_code(405);
            die("Method Not Allowed = ". self::$allowedMethod);
        }
        return true;
    }
    public static function getMethod(): string
    {
        return $_SERVER["REQUEST_METHOD"] ?? "GET";
    }
    public static function isPost(): bool
    {
        return self::getMethod() === "POST";
    }
    /**
     * check if current request is GET
     * @return bool True if GET, false otherwise
     */
    public static function isGet(): bool
    {
        return self::getMethod() === "GET";
    }

    /**
     * get allowed method
     * @return string
     */
    public static function getAllowedMethod(): string
    {
        return self::$allowedMethod;
    }
}
