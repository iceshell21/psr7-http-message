<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Security;

use IceShell21\Psr7HttpMessage\Security\SecurityValidator;
use IceShell21\Psr7HttpMessage\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for Security Validator functionality.
 */
class SecurityValidatorTest extends TestCase
{
    private SecurityValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SecurityValidator(strictMode: true);
    }

    public function testValidHttpMethods(): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH'];
        
        foreach ($validMethods as $method) {
            $this->validator->validateHttpMethod($method);
            $this->assertTrue(true); // If we get here, validation passed
        }
    }

    public function testInvalidHttpMethods(): void
    {
        $invalidMethods = [
            '', // Empty
            'INVALID_METHOD_NAME_TOO_LONG', // Too long
            'GET123', // Contains numbers
            'G E T', // Contains spaces
            "GET\r\n", // Contains control characters
        ];
        
        foreach ($invalidMethods as $method) {
            $this->expectException(InvalidArgumentException::class);
            $this->validator->validateHttpMethod($method);
        }
    }

    public function testValidUris(): void
    {
        $validUris = [
            'https://example.com',
            'http://localhost:8080/path',
            '/relative/path',
            '',
        ];
        
        foreach ($validUris as $uri) {
            $this->validator->validateUri($uri);
            $this->assertTrue(true); // If we get here, validation passed
        }
    }

    public function testInvalidUris(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $longUri = 'https://example.com/' . str_repeat('a', 3000); // Too long
        $this->validator->validateUri($longUri);
    }

    public function testScriptInjectionInUri(): void
    {
        $dangerousUris = [
            'javascript:alert("xss")',
            'data:text/html,<script>alert("xss")</script>',
            'vbscript:msgbox("xss")',
        ];
        
        foreach ($dangerousUris as $uri) {
            $this->expectException(InvalidArgumentException::class);
            $this->validator->validateUri($uri);
        }
    }

    public function testControlCharactersInUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUri("https://example.com\x00\x01");
    }

    public function testValidHeaderNames(): void
    {
        $validNames = [
            'Content-Type',
            'X-Custom-Header',
            'Authorization',
            'Accept',
        ];
        
        foreach ($validNames as $name) {
            $this->validator->validateHeaderName($name);
            $this->assertTrue(true); // If we get here, validation passed
        }
    }

    public function testInvalidHeaderNames(): void
    {
        $invalidNames = [
            '', // Empty
            str_repeat('a', 200), // Too long
            'Invalid Header Name', // Contains space
            'Header<>Name', // Contains invalid characters
        ];
        
        foreach ($invalidNames as $name) {
            $this->expectException(InvalidArgumentException::class);
            $this->validator->validateHeaderName($name);
        }
    }

    public function testHeaderValueValidation(): void
    {
        $validValues = [
            'application/json',
            'Bearer token123',
            ['value1', 'value2'], // Array values
        ];
        
        foreach ($validValues as $value) {
            $this->validator->validateHeaderValue($value);
            $this->assertTrue(true); // If we get here, validation passed
        }
    }

    public function testCRLFInjectionInHeaders(): void
    {
        $dangerousValues = [
            "value\r\nInjected-Header: malicious",
            "value\nAnother-Injection: bad",
            ['good-value', "bad\r\nvalue"],
        ];
        
        foreach ($dangerousValues as $value) {
            $this->expectException(InvalidArgumentException::class);
            $this->validator->validateHeaderValue($value);
        }
    }

    public function testHeaderValueTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $longValue = str_repeat('a', 10000); // Too long
        $this->validator->validateHeaderValue($longValue);
    }

    public function testScriptInjectionInHeaderValue(): void
    {
        $dangerousValues = [
            '<script>alert("xss")</script>',
            'javascript:alert("xss")',
            'data:text/html,<script>',
        ];
        
        foreach ($dangerousValues as $value) {
            $this->expectException(InvalidArgumentException::class);
            $this->validator->validateHeaderValue($value);
        }
    }

    public function testHeadersValidation(): void
    {
        $validHeaders = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer token',
            'X-Custom' => ['value1', 'value2'],
        ];
        
        $this->validator->validateHeaders($validHeaders);
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function testTooManyHeaders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $tooManyHeaders = [];
        for ($i = 0; $i < 150; $i++) { // More than MAX_HEADERS_COUNT
            $tooManyHeaders["Header-$i"] = "value-$i";
        }
        
        $this->validator->validateHeaders($tooManyHeaders);
    }

    public function testHeadersSizeLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        // Create headers that exceed total size limit
        $largeHeaders = [
            'Large-Header-1' => str_repeat('x', 30000),
            'Large-Header-2' => str_repeat('y', 30000),
            'Large-Header-3' => str_repeat('z', 30000),
        ];
        
        $this->validator->validateHeaders($largeHeaders);
    }

    public function testValidStatusCodes(): void
    {
        $validCodes = [100, 200, 301, 404, 500, 599];
        
        foreach ($validCodes as $code) {
            $this->validator->validateStatusCode($code);
            $this->assertTrue(true); // If we get here, validation passed
        }
    }

    public function testInvalidStatusCodes(): void
    {
        $invalidCodes = [99, 600, 700, -1];
        
        foreach ($invalidCodes as $code) {
            $this->expectException(InvalidArgumentException::class);
            $this->validator->validateStatusCode($code);
        }
    }

    public function testReasonPhraseValidation(): void
    {
        $validPhrases = ['OK', 'Not Found', 'Internal Server Error', ''];
        
        foreach ($validPhrases as $phrase) {
            $this->validator->validateReasonPhrase($phrase);
            $this->assertTrue(true); // If we get here, validation passed
        }
    }

    public function testInvalidReasonPhrases(): void
    {
        $invalidPhrases = [
            str_repeat('a', 300), // Too long
            "OK\r\nInjected: header", // CRLF injection
            "OK\x00", // Control characters
        ];
        
        foreach ($invalidPhrases as $phrase) {
            $this->expectException(InvalidArgumentException::class);
            $this->validator->validateReasonPhrase($phrase);
        }
    }

    public function testRequestTargetValidation(): void
    {
        $validTargets = [
            '/',
            '/api/users',
            '/path?query=value',
            '*',
            'https://example.com/absolute',
        ];
        
        foreach ($validTargets as $target) {
            $this->validator->validateRequestTarget($target);
            $this->assertTrue(true); // If we get here, validation passed
        }
    }

    public function testInvalidRequestTargets(): void
    {
        $invalidTargets = [
            '', // Empty
            str_repeat('a', 3000), // Too long
            'javascript:alert("xss")', // Script injection
            'invalid-format', // Invalid format
        ];
        
        foreach ($invalidTargets as $target) {
            $this->expectException(InvalidArgumentException::class);
            $this->validator->validateRequestTarget($target);
        }
    }

    public function testUriObjectValidation(): void
    {
        $validUri = new Uri('https://example.com:443/path');
        $this->validator->validateUriObject($validUri);
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function testInvalidUriScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        // Create validator with limited schemes
        $validator = new SecurityValidator(strictMode: true, allowedSchemes: ['https']);
        $invalidUri = new Uri('http://example.com'); // http not allowed
        
        $validator->validateUriObject($invalidUri);
    }

    public function testInvalidUriPort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $invalidUri = new Uri('https://example.com:99999'); // Invalid port
        $this->validator->validateUriObject($invalidUri);
    }

    public function testSecurityConfiguration(): void
    {
        $config = $this->validator->getConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('strict_mode', $config);
        $this->assertArrayHasKey('allowed_schemes', $config);
        $this->assertArrayHasKey('limits', $config);
        $this->assertTrue($config['strict_mode']);
    }

    public function testNonStrictMode(): void
    {
        $lenientValidator = new SecurityValidator(strictMode: false);
        
        // This should pass in non-strict mode
        $lenientValidator->validateHeaderValue('<script>alert("test")</script>');
        $this->assertTrue(true); // If we get here, validation passed
    }
}