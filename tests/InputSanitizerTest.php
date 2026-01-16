<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Tests\Security;

use PHPUnit\Framework\TestCase;
use SFORM\FormValidator\Security\InputSanitizer;

class InputSanitizerTest extends TestCase
{
    public function testSanitizeString(): void
    {
        $input = "<script>alert('xss')</script>Hello";
        $result = InputSanitizer::sanitizeString($input);
        
        $this->assertEquals("alert('xss')Hello", $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testSanitizeEmail(): void
    {
        $validEmail = "test@example.com";
        $invalidEmail = "not-an-email";
        
        $this->assertEquals($validEmail, InputSanitizer::sanitizeEmail($validEmail));
        $this->assertFalse(InputSanitizer::sanitizeEmail($invalidEmail));
    }

    public function testSanitizeUrl(): void
    {
        $validUrl = "https://example.com";
        $invalidUrl = "not a url";
        
        $this->assertEquals($validUrl, InputSanitizer::sanitizeUrl($validUrl));
        $this->assertFalse(InputSanitizer::sanitizeUrl($invalidUrl));
    }

    public function testSanitizeInt(): void
    {
        $this->assertEquals(123, InputSanitizer::sanitizeInt("123"));
        $this->assertEquals(-456, InputSanitizer::sanitizeInt("-456"));
        $this->assertFalse(InputSanitizer::sanitizeInt("abc"));
    }

    public function testSanitizeFloat(): void
    {
        $this->assertEquals(123.45, InputSanitizer::sanitizeFloat("123.45"));
        $this->assertEquals(-67.89, InputSanitizer::sanitizeFloat("-67.89"));
        $this->assertFalse(InputSanitizer::sanitizeFloat("not a number"));
    }

    public function testEscapeHtml(): void
    {
        $input = '<script>alert("xss")</script>';
        $result = InputSanitizer::escapeHtml($input);
        
        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testSanitizeFilename(): void
    {
        $dangerous = "../../../etc/passwd";
        $result = InputSanitizer::sanitizeFilename($dangerous);
        
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
        $this->assertEquals("etcpasswd", $result);
    }

    public function testRemoveNullBytes(): void
    {
        $input = "test\0string";
        $result = InputSanitizer::removeNullBytes($input);
        
        $this->assertEquals("teststring", $result);
        $this->assertStringNotContainsString("\0", $result);
    }

    public function testLimitLength(): void
    {
        $longString = str_repeat("a", 2000);
        $result = InputSanitizer::limitLength($longString, 100);
        
        $this->assertEquals(100, mb_strlen($result));
    }

    public function testSanitizeArray(): void
    {
        $input = [
            'name' => '<script>alert("xss")</script>John',
            'nested' => [
                'value' => '<b>Bold</b>'
            ]
        ];
        
        $result = InputSanitizer::sanitizeArray($input);
        
        $this->assertStringNotContainsString('<script>', $result['name']);
        $this->assertStringNotContainsString('<b>', $result['nested']['value']);
    }
}