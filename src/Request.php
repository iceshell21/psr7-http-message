<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage;

use IceShell21\Psr7HttpMessage\Enum\HttpMethod;
use IceShell21\Psr7HttpMessage\Trait\MessageTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * HTTP request implementation with immutable design.
 */
class Request implements RequestInterface
{
    use MessageTrait;

    private ?string $requestTarget = null;
    private UriInterface $uri;

    public function __construct(
        private HttpMethod $method = HttpMethod::GET,
        UriInterface|string $uri = '',
        array $headers = [],
        private ?StreamInterface $body = null,
        private string $protocolVersion = '1.1'
    ) {
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->body = $this->createBody($body);
        $this->initializeHeaders($headers);
        $this->addHostHeaderIfNeeded();
    }

    private function createBody(mixed $body): ?StreamInterface
    {
        return match (true) {
            $body === null => null,
            $body instanceof StreamInterface => $body,
            default => new Stream($body)
        };
    }

    private function addHostHeaderIfNeeded(): void
    {
        if (!$this->hasHeader('Host') && $this->uri->getHost()) {
            $this->headerCollection = $this->headerCollection->with('host', $this->getHostFromUri());
        }
    }

    public function getRequestTarget(): string
    {
        return $this->requestTarget ??= $this->createRequestTarget();
    }

    private function createRequestTarget(): string
    {
        $target = $this->uri->getPath() ?: '/';
        $query = $this->uri->getQuery();
        
        return $query !== '' ? "{$target}?{$query}" : $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        return $this->cloneWith('requestTarget', $requestTarget);
    }

    public function getMethod(): string
    {
        return $this->method->value;
    }

    public function withMethod(string $method): static
    {
        return $this->cloneWith('method', HttpMethod::fromString($method));
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone $this;
        $new->uri = $uri;
        
        if (!$preserveHost || !$this->hasHeader('Host')) {
            $new->headerCollection = $new->headerCollection->with('host', $new->getHostFromUri());
        }
        
        return $new;
    }

    private function getHostFromUri(): string
    {
        $host = $this->uri->getHost();
        $port = $this->uri->getPort();
        
        return $port !== null ? "{$host}:{$port}" : $host;
    }
}
