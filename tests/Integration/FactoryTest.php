<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Integration;

use IceShell21\Psr7HttpMessage\Factory\RequestFactory;
use IceShell21\Psr7HttpMessage\Factory\ResponseFactory;
use IceShell21\Psr7HttpMessage\Factory\ServerRequestFactory;
use IceShell21\Psr7HttpMessage\Factory\StreamFactory;
use IceShell21\Psr7HttpMessage\Factory\UriFactory;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for PSR-17 factories.
 */
final class FactoryTest extends TestCase
{
    public function testRequestFactory(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('POST', 'https://example.com/api');
        
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://example.com/api', (string) $request->getUri());
    }

    public function testResponseFactory(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse(201, 'Created');
        
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created', $response->getReasonPhrase());
    }

    public function testServerRequestFactory(): void
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('GET', 'https://example.com', ['HTTP_HOST' => 'example.com']);
        
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://example.com', (string) $request->getUri());
        $this->assertSame(['HTTP_HOST' => 'example.com'], $request->getServerParams());
    }

    public function testStreamFactory(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('test content');
        
        $this->assertSame('test content', (string) $stream);
    }

    public function testUriFactory(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('https://example.com/path?query=value');
        
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/path', $uri->getPath());
        $this->assertSame('query=value', $uri->getQuery());
    }
}
