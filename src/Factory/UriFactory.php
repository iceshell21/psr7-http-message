<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Factory;

use IceShell21\Psr7HttpMessage\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-17 HTTP URI Factory implementation.
 */
final class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
