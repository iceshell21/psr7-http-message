<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Enum;

/**
 * HTTP method enumeration with safe string conversion.
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case PATCH = 'PATCH';
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

    /**
     * Safely convert string to HttpMethod enum.
     * Returns GET as default for invalid methods.
     */
    public static function fromString(string $method): self
    {
        return self::tryFrom(mb_strtoupper($method, 'UTF-8')) ?? self::GET;
    }
}
