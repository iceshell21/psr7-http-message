<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Integration;

use IceShell21\Psr7HttpMessage\Response\JsonResponse;
use IceShell21\Psr7HttpMessage\Response\HtmlResponse;
use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for response emission.
 */
final class ResponseTest extends TestCase
{
    public function testJsonResponseCreation(): void
    {
        $data = ['message' => 'Hello, World!', 'status' => 'success'];
        $response = new JsonResponse($data, HttpStatusCode::OK);
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
        $this->assertSame('{"message":"Hello, World!","status":"success"}', (string) $response->getBody());
    }

    public function testHtmlResponseCreation(): void
    {
        $html = '<h1>Hello, World!</h1>';
        $response = new HtmlResponse($html, HttpStatusCode::OK);
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
        $this->assertSame($html, (string) $response->getBody());
    }
}
