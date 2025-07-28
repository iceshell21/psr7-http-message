<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Unit\Enum;

use IceShell21\Psr7HttpMessage\Enum\HttpMethod;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IceShell21\Psr7HttpMessage\Enum\HttpMethod
 */
final class HttpMethodTest extends TestCase
{
    public function testFromStringValid(): void
    {
        $this->assertSame(HttpMethod::GET, HttpMethod::fromString('GET'));
        $this->assertSame(HttpMethod::POST, HttpMethod::fromString('post'));
        $this->assertSame(HttpMethod::PUT, HttpMethod::fromString('Put'));
    }

    public function testFromStringInvalid(): void
    {
        $this->assertSame(HttpMethod::GET, HttpMethod::fromString('INVALID'));
        $this->assertSame(HttpMethod::GET, HttpMethod::fromString(''));
    }

    public function testValues(): void
    {
        $this->assertSame('GET', HttpMethod::GET->value);
        $this->assertSame('POST', HttpMethod::POST->value);
        $this->assertSame('PUT', HttpMethod::PUT->value);
        $this->assertSame('DELETE', HttpMethod::DELETE->value);
        $this->assertSame('HEAD', HttpMethod::HEAD->value);
        $this->assertSame('OPTIONS', HttpMethod::OPTIONS->value);
        $this->assertSame('PATCH', HttpMethod::PATCH->value);
        $this->assertSame('TRACE', HttpMethod::TRACE->value);
        $this->assertSame('CONNECT', HttpMethod::CONNECT->value);
    }
}
