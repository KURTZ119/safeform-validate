<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Tests;

use PHPUnit\Framework\TestCase;
use SFORM\FormValidator\Form\Xcsrf;

class XcsrfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear session before each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_POST = [];
        
        parent::tearDown();
    }

    public function testTokenGenerationCreatesHiddenInput(): void
    {
        ob_start();
        Xcsrf::token();
        $output = ob_get_clean();

        $this->assertStringContainsString('<input type="hidden"', $output);
        $this->assertStringContainsString('name="_csrf_token"', $output);
        $this->assertStringContainsString('value="', $output);
    }

    public function testTokenIsStoredInSession(): void
    {
        ob_start();
        Xcsrf::token();
        ob_end_clean();

        $this->assertArrayHasKey('csrf_tokens', $_SESSION);
        $this->assertIsArray($_SESSION['csrf_tokens']);
        $this->assertNotEmpty($_SESSION['csrf_tokens']);
    }

    public function testValidateReturnsFalseForGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $result = Xcsrf::validate();
        
        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseWhenTokenNotInPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $result = Xcsrf::validate();
        
        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseForInvalidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_csrf_token'] = 'invalid_token_12345';
        
        $result = Xcsrf::validate();
        
        $this->assertFalse($result);
    }

    public function testValidateReturnsTrueForValidToken(): void
    {
        // Generate token
        ob_start();
        Xcsrf::token();
        $output = ob_get_clean();

        // Extract token from output
        preg_match('/value="([^"]+)"/', $output, $matches);
        $token = $matches[1];

        // Simulate POST request with valid token
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_csrf_token'] = $token;

        $result = Xcsrf::validate();

        $this->assertTrue($result);
    }

    public function testTokenIsRemovedAfterSuccessfulValidation(): void
    {
        // Generate token
        ob_start();
        Xcsrf::token();
        $output = ob_get_clean();

        preg_match('/value="([^"]+)"/', $output, $matches);
        $token = $matches[1];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_csrf_token'] = $token;

        // First validation should succeed
        $this->assertTrue(Xcsrf::validate());

        // Second validation with same token should fail (one-time use)
        $this->assertFalse(Xcsrf::validate());
    }

    public function testExpiredTokenIsRejected(): void
    {
        // Generate token
        ob_start();
        Xcsrf::token();
        $output = ob_get_clean();

        preg_match('/value="([^"]+)"/', $output, $matches);
        $token = $matches[1];

        // Manually set token timestamp to expired (more than 1 hour ago)
        $_SESSION['csrf_tokens'][$token] = time() - 3601;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_csrf_token'] = $token;

        $result = Xcsrf::validate();

        $this->assertFalse($result);
    }

    public function testMultipleTokensCanBeGenerated(): void
    {
        $tokens = [];

        for ($i = 0; $i < 5; $i++) {
            ob_start();
            Xcsrf::token();
            $output = ob_get_clean();

            preg_match('/value="([^"]+)"/', $output, $matches);
            $tokens[] = $matches[1];
        }

        // All tokens should be unique
        $this->assertCount(5, array_unique($tokens));

        // All tokens should be in session
        $this->assertCount(5, $_SESSION['csrf_tokens']);
    }

    public function testOldTokensAreCleanedUp(): void
    {
        // Generate more than 10 tokens
        for ($i = 0; $i < 15; $i++) {
            ob_start();
            Xcsrf::token();
            ob_end_clean();
        }

        // Only last 10 should remain
        $this->assertLessThanOrEqual(10, count($_SESSION['csrf_tokens']));
    }

    public function testSessionIsAutomaticallyStarted(): void
    {
        // Ensure session is not active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        ob_start();
        Xcsrf::token();
        ob_end_clean();

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }
}