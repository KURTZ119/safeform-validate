<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Tests\Security;

use PHPUnit\Framework\TestCase;
use SFORM\FormValidator\Security\RedirectValidator;

class RedirectValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset configuration
        RedirectValidator::setAllowedHosts([]);
        RedirectValidator::setAllowRelativeUrls(true);
        
        $_SERVER['HTTP_HOST'] = 'example.com';
    }

    public function testRelativeUrlIsValid(): void
    {
        $this->assertTrue(RedirectValidator::isValidRedirectUrl('/dashboard'));
        $this->assertTrue(RedirectValidator::isValidRedirectUrl('/user/profile'));
    }

    public function testDirectoryTraversalIsBlocked(): void
    {
        $this->assertFalse(RedirectValidator::isValidRedirectUrl('../../../etc/passwd'));
        $this->assertFalse(RedirectValidator::isValidRedirectUrl('/path/../../../etc/passwd'));
    }

    public function testJavaScriptProtocolIsBlocked(): void
    {
        $this->assertFalse(RedirectValidator::isValidRedirectUrl('javascript:alert(1)'));
        $this->assertFalse(RedirectValidator::isValidRedirectUrl('JavaScript:alert(1)'));
    }

    public function testDataProtocolIsBlocked(): void
    {
        $this->assertFalse(RedirectValidator::isValidRedirectUrl('data:text/html,<script>alert(1)</script>'));
    }

    public function testNullByteIsBlocked(): void
    {
        $this->assertFalse(RedirectValidator::isValidRedirectUrl("/path\0/file"));
    }

    public function testSameHostIsAllowed(): void
    {
        $this->assertTrue(RedirectValidator::isValidRedirectUrl('http://example.com/page'));
        $this->assertTrue(RedirectValidator::isValidRedirectUrl('https://example.com/page'));
    }

    public function testDifferentHostIsBlockedByDefault(): void
    {
        $this->assertFalse(RedirectValidator::isValidRedirectUrl('http://evil.com/phishing'));
    }

    public function testAllowedHostsWhitelist(): void
    {
        RedirectValidator::setAllowedHosts(['trusted.com', 'example.com']);
        
        $this->assertTrue(RedirectValidator::isValidRedirectUrl('https://trusted.com/page'));
        $this->assertFalse(RedirectValidator::isValidRedirectUrl('https://evil.com/page'));
    }

    public function testSanitizeRedirectUrl(): void
    {
        $safe = RedirectValidator::sanitizeRedirectUrl('/dashboard', '/');
        $this->assertEquals('/dashboard', $safe);
        
        $unsafe = RedirectValidator::sanitizeRedirectUrl('javascript:alert(1)', '/');
        $this->assertEquals('/', $unsafe);
    }

    public function testRelativeUrlsCanBeDisabled(): void
    {
        RedirectValidator::setAllowRelativeUrls(false);
        
        $this->assertFalse(RedirectValidator::isValidRedirectUrl('/dashboard'));
    }
}