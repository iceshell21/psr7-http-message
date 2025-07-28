<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Factory;

use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;
use IceShell21\Psr7HttpMessage\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-17 HTTP Response Factory implementation.
 */
final class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response(
            statusCode: HttpStatusCode::from($code),
            reasonPhrase: $reasonPhrase ?: null
        );
    }
}
