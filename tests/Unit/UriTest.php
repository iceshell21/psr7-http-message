<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Unit;

use IceShell21\Psr7HttpMessage\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IceShell21\Psr7HttpMessage\Uri
 */
final class UriTest extends TestCase
{
    public function testConstructorWithString(): void
    {
        $uri = new Uri('https://user:pass@example.com:8080/path?query=value#fragment');
        
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/path', $uri->getPath());
        $this->assertSame('query=value', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
    }

    public function testConstructorWithComponents(): void
    {
        $uri = new Uri('https', 'user:pass', 'example.com', 8080, '/path', 'query=value', 'fragment');
        
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/path', $uri->getPath());
        $this->assertSame('query=value', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
    }

    public function testGetAuthority(): void
    {
        $uri = new Uri('https://user:pass@example.com:8080/path');
        
        $this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
    }

    public function testGetAuthorityWithoutUserInfo(): void
    {
        $uri = new Uri('https://example.com:8080/path');
        
        $this->assertSame('example.com:8080', $uri->getAuthority());
    }

    public function testGetAuthorityWithoutPort(): void
    {
        $uri = new Uri('https://example.com/path');
        
        $this->assertSame('example.com', $uri->getAuthority());
    }

    public function testWithScheme(): void
    {
        $uri = new Uri('http://example.com');
        $newUri = $uri->withScheme('https');
        
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('https', $newUri->getScheme());
    }

    public function testWithUserInfo(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withUserInfo('user', 'pass');
        
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('user:pass', $newUri->getUserInfo());
    }

    public function testWithHost(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withHost('test.com');
        
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('test.com', $newUri->getHost());
    }

    public function testWithPort(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withPort(8080);
        
        $this->assertNull($uri->getPort());
        $this->assertSame(8080, $newUri->getPort());
    }

    public function testWithPath(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withPath('/new/path');
        
        $this->assertSame('', $uri->getPath());
        $this->assertSame('/new/path', $newUri->getPath());
    }

    public function testWithQuery(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withQuery('foo=bar');
        
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('foo=bar', $newUri->getQuery());
    }

    public function testWithFragment(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withFragment('section');
        
        $this->assertSame('', $uri->getFragment());
        $this->assertSame('section', $newUri->getFragment());
    }

    public function testToString(): void
    {
        $uri = new Uri('https://user:pass@example.com:8080/path?query=value#fragment');
        
        $this->assertSame('https://user:pass@example.com:8080/path?query=value#fragment', (string) $uri);
    }

    public function testInvalidUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Uri('http:///invalid');
    }

    public function testStandardPortRemoval(): void
    {
        $httpUri = new Uri('http://example.com:80');
        $httpsUri = new Uri('https://example.com:443');
        
        $this->assertNull($httpUri->getPort());
        $this->assertNull($httpsUri->getPort());
    }
}
