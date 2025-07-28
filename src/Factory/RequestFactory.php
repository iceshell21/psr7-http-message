<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Factory;

use IceShell21\Psr7HttpMessage\Enum\HttpMethod;
use IceShell21\Psr7HttpMessage\Request;
use IceShell21\Psr7HttpMessage\Uri;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * PSR-17 HTTP Request Factory implementation.
 */
final class RequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request(
            HttpMethod::fromString($method),
            is_string($uri) ? new Uri($uri) : $uri
        );
    }
}
