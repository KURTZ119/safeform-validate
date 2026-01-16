<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Tests;

use PHPUnit\Framework\TestCase;

use function SFORM\FormValidator\xcsrf_token;
use function SFORM\FormValidator\xcsrf_is_valid;


class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_POST = [];
        
        parent::tearDown();
    }

    public function testXcsrfTokenFunctionExists(): void
    {
        $this->assertTrue(function_exists('SFORM\FormValidator\xcsrf_token'));
    }

    public function testXcsrfTokenGeneratesOutput(): void
    {
        ob_start();
        xcsrf_token();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('_csrf_token', $output);
    }

    public function testXcsrfIsValidFunctionExists(): void
    {
        $this->assertTrue(function_exists('SFORM\FormValidator\xcsrf_is_valid'));
    }

    public function testXcsrfIsValidReturnsFalseForGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $result = xcsrf_is_valid();
        
        $this->assertFalse($result);
    }

    public function testXcsrfIsValidReturnsTrueForValidToken(): void
    {
        ob_start();
        xcsrf_token();
        $output = ob_get_clean();

        preg_match('/value="([^"]+)"/', $output, $matches);
        $token = $matches[1];

        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST["_csrf_token"] = $token;
        $result = xcsrf_is_valid();
        $this->assertTrue($result);
    }

    public function testIsErrorFunctionExists(): void
    {
        $this->assertTrue(function_exists('SFORM\FormValidator\is_error'));
    }

    public function testIsValidFunctionExists(): void
    {
        $this->assertTrue(function_exists('SFORM\FormValidator\is_valid'));
    }
}