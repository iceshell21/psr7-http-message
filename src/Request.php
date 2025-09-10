<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage;

use IceShell21\Psr7HttpMessage\Enum\HttpMethod;
use IceShell21\Psr7HttpMessage\Trait\MessageTrait;
use IceShell21\Psr7HttpMessage\Security\SecurityValidator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * HTTP request implementation with immutable design.
 * Enhanced with PHP 8.4+ features, security validation and performance monitoring.
 */
class Request implements RequestInterface
{
    use MessageTrait;

    private ?string $requestTarget = null;
    private UriInterface $uri;
    private ?SecurityValidator $validator = null;
    
    // Property hook for computed request target (PHP 8.4+)
    public string $computedRequestTarget {
        get => $this->requestTarget ??= $this->createRequestTarget();
    }
    
    // Asymmetric visibility for performance stats (PHP 8.4+)
    public private(set) array $performanceStats = [
        'request_target_computed' => false,
        'headers_validated' => false,
        'uri_validated' => false,
    ];

    public function __construct(
        private HttpMethod $method = HttpMethod::GET,
        UriInterface|string $uri = '',
        array $headers = [],
        private ?StreamInterface $body = null,
        private string $protocolVersion = '1.1'
    ) {
        // Initialize security validator
        $this->validator = new SecurityValidator(strictMode: false);
        
        // Validate HTTP method
        $this->validator->validateHttpMethod($this->method->value);
        
        // Convert and validate URI
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->validator->validateUriObject($this->uri);
        $this->performanceStats['uri_validated'] = true;
        
        // Create body and initialize headers
        $this->body = $this->createBody($body);
        $this->initializeHeaders($headers);
        $this->performanceStats['headers_validated'] = true;
        
        // Auto-add Host header if needed
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
        if ($this->requestTarget === null) {
            $this->requestTarget = $this->createRequestTarget();
            $this->performanceStats['request_target_computed'] = true;
        }
        return $this->requestTarget;
    }

    private function createRequestTarget(): string
    {
        $target = $this->uri->getPath() ?: '/';
        $query = $this->uri->getQuery();
        
        return $query !== '' ? "{$target}?{$query}" : $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        // Validate request target
        $this->validator?->validateRequestTarget($requestTarget);
        
        return $this->cloneWith('requestTarget', $requestTarget);
    }

    public function getMethod(): string
    {
        return $this->method->value;
    }

    public function withMethod(string $method): static
    {
        // Validate HTTP method
        $this->validator?->validateHttpMethod($method);
        
        return $this->cloneWith('method', HttpMethod::fromString($method));
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        // Validate new URI
        $this->validator?->validateUriObject($uri);
        
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
    
    /**
     * Get performance and security statistics.
     */
    public function getStats(): array
    {
        return [
            'performance_stats' => $this->performanceStats,
            'method' => $this->method->value,
            'uri_scheme' => $this->uri->getScheme(),
            'headers_count' => count($this->getHeaders()),
            'body_size' => $this->body?->getSize(),
            'protocol_version' => $this->protocolVersion,
        ];
    }
    
    /**
     * Validate entire request for security compliance.
     */
    public function validateSecurity(): bool
    {
        try {
            $this->validator?->validateHttpMethod($this->method->value);
            $this->validator?->validateUriObject($this->uri);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
