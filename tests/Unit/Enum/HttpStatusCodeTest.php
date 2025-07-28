<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Unit\Enum;

use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IceShell21\Psr7HttpMessage\Enum\HttpStatusCode
 */
final class HttpStatusCodeTest extends TestCase
{
    public function testGetReasonPhrase(): void
    {
        $this->assertSame('OK', HttpStatusCode::OK->getReasonPhrase());
        $this->assertSame('Not Found', HttpStatusCode::NOT_FOUND->getReasonPhrase());
        $this->assertSame('Internal Server Error', HttpStatusCode::INTERNAL_SERVER_ERROR->getReasonPhrase());
        $this->assertSame('I\'m a teapot', HttpStatusCode::IM_A_TEAPOT->getReasonPhrase());
    }

    public function testValues(): void
    {
        $this->assertSame(200, HttpStatusCode::OK->value);
        $this->assertSame(404, HttpStatusCode::NOT_FOUND->value);
        $this->assertSame(500, HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        $this->assertSame(418, HttpStatusCode::IM_A_TEAPOT->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(HttpStatusCode::OK, HttpStatusCode::from(200));
        $this->assertSame(HttpStatusCode::NOT_FOUND, HttpStatusCode::from(404));
        $this->assertSame(HttpStatusCode::INTERNAL_SERVER_ERROR, HttpStatusCode::from(500));
    }
}
