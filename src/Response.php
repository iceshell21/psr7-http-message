<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage;

use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;
use IceShell21\Psr7HttpMessage\Trait\MessageTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP response implementation with immutable design.
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    private ?StreamInterface $body;

    public function __construct(
        mixed $body = null,
        private HttpStatusCode $statusCode = HttpStatusCode::OK,
        array $headers = [],
        private string $protocolVersion = '1.1',
        private ?string $reasonPhrase = null
    ) {
        $this->body = $this->createBody($body);
        $this->initializeHeaders($headers);
    }

    private function createBody(mixed $body): ?StreamInterface
    {
        return match (true) {
            $body === null => null,
            $body instanceof StreamInterface => $body,
            default => new Stream($body)
        };
    }

    public function getStatusCode(): int
    {
        return $this->statusCode->value;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $new = clone $this;
        $new->statusCode = HttpStatusCode::from($code);
        $new->reasonPhrase = $reasonPhrase ?: $new->statusCode->getReasonPhrase();
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase ?? $this->statusCode->getReasonPhrase();
    }
}
