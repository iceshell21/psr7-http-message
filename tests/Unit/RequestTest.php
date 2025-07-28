<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Unit;

use IceShell21\Psr7HttpMessage\Enum\HttpMethod;
use IceShell21\Psr7HttpMessage\Request;
use IceShell21\Psr7HttpMessage\Stream;
use IceShell21\Psr7HttpMessage\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IceShell21\Psr7HttpMessage\Request
 */
final class RequestTest extends TestCase
{
    public function testConstructor(): void
    {
        $uri = new Uri('https://example.com/path');
        $body = new Stream('test body');
        $headers = ['Content-Type' => 'application/json'];
        
        $request = new Request(HttpMethod::POST, $uri, $headers, $body, '2.0');
        
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame($uri, $request->getUri());
        $this->assertSame($body, $request->getBody());
        $this->assertSame('2.0', $request->getProtocolVersion());
        $this->assertTrue($request->hasHeader('Content-Type'));
    }

    public function testGetRequestTarget(): void
    {
        $request = new Request(HttpMethod::GET, 'https://example.com/path?query=value');
        
        $this->assertSame('/path?query=value', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithoutPath(): void
    {
        $request = new Request(HttpMethod::GET, 'https://example.com');
        
        $this->assertSame('/', $request->getRequestTarget());
    }

    public function testWithRequestTarget(): void
    {
        $request = new Request(HttpMethod::GET, 'https://example.com');
        $newRequest = $request->withRequestTarget('/custom/target');
        
        $this->assertSame('/', $request->getRequestTarget());
        $this->assertSame('/custom/target', $newRequest->getRequestTarget());
    }

    public function testWithMethod(): void
    {
        $request = new Request(HttpMethod::GET);
        $newRequest = $request->withMethod('POST');
        
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('POST', $newRequest->getMethod());
    }

    public function testWithUri(): void
    {
        $uri1 = new Uri('https://example.com');
        $uri2 = new Uri('https://test.com');
        
        $request = new Request(HttpMethod::GET, $uri1);
        $newRequest = $request->withUri($uri2);
        
        $this->assertSame($uri1, $request->getUri());
        $this->assertSame($uri2, $newRequest->getUri());
    }

    public function testWithUriPreserveHost(): void
    {
        $request = new Request(HttpMethod::GET, 'https://example.com', ['Host' => 'original.com']);
        $newRequest = $request->withUri(new Uri('https://test.com'), true);
        
        $this->assertSame(['original.com'], $newRequest->getHeader('Host'));
    }

    public function testHostHeaderAutomatic(): void
    {
        $request = new Request(HttpMethod::GET, 'https://example.com:8080');
        
        $this->assertSame(['example.com:8080'], $request->getHeader('Host'));
    }

    public function testHostHeaderNotOverridden(): void
    {
        $request = new Request(HttpMethod::GET, 'https://example.com', ['Host' => 'custom.com']);
        
        $this->assertSame(['custom.com'], $request->getHeader('Host'));
    }
}
