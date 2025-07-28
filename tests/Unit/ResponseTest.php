<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Unit;

use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;
use IceShell21\Psr7HttpMessage\Response;
use IceShell21\Psr7HttpMessage\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IceShell21\Psr7HttpMessage\Response
 */
final class ResponseTest extends TestCase
{
    public function testConstructor(): void
    {
        $body = new Stream('test body');
        $headers = ['Content-Type' => 'text/plain'];
        
        $response = new Response($body, HttpStatusCode::CREATED, $headers, '2.0', 'Custom Reason');
        
        $this->assertSame($body, $response->getBody());
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Custom Reason', $response->getReasonPhrase());
        $this->assertSame('2.0', $response->getProtocolVersion());
        $this->assertTrue($response->hasHeader('Content-Type'));
    }

    public function testDefaultValues(): void
    {
        $response = new Response();
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('1.1', $response->getProtocolVersion());
    }

    public function testWithStatus(): void
    {
        $response = new Response();
        $newResponse = $response->withStatus(404, 'Custom Not Found');
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        
        $this->assertSame(404, $newResponse->getStatusCode());
        $this->assertSame('Custom Not Found', $newResponse->getReasonPhrase());
    }

    public function testWithStatusDefaultReason(): void
    {
        $response = new Response();
        $newResponse = $response->withStatus(404);
        
        $this->assertSame(404, $newResponse->getStatusCode());
        $this->assertSame('Not Found', $newResponse->getReasonPhrase());
    }

    public function testGetReasonPhraseFromEnum(): void
    {
        $response = new Response(statusCode: HttpStatusCode::NOT_FOUND);
        
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testBodyCreation(): void
    {
        $response = new Response('string body');
        
        $this->assertSame('string body', (string) $response->getBody());
    }
}
