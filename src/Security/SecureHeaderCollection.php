<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Security;

use IceShell21\Psr7HttpMessage\Collection\HeaderCollection;
use InvalidArgumentException;

/**
 * Security-enhanced header collection that validates all operations.
 * Prevents header injection attacks and enforces strict validation.
 */
final readonly class SecureHeaderCollection
{
    private HeaderCollection $headers;
    private SecurityValidator $validator;

    public function __construct(
        array $headers = [],
        ?SecurityValidator $validator = null
    ) {
        $this->validator = $validator ?? new SecurityValidator();
        
        // Validate all initial headers
        $this->validator->validateHeaders($headers);
        
        $this->headers = new HeaderCollection($headers);
    }

    /**
     * Add header with security validation.
     */
    public function with(string $name, mixed $value): self
    {
        $this->validator->validateHeaderName($name);
        $this->validator->validateHeaderValue($value);

        $newHeaders = $this->headers->with($name, $value);
        
        return new self($newHeaders->toArray(), $this->validator);
    }

    /**
     * Add header value with security validation.
     */
    public function withAddedValue(string $name, mixed $value): self
    {
        $this->validator->validateHeaderName($name);
        $this->validator->validateHeaderValue($value);

        $newHeaders = $this->headers->withAddedValue($name, $value);
        
        return new self($newHeaders->toArray(), $this->validator);
    }

    /**
     * Remove header.
     */
    public function without(string $name): self
    {
        // Still validate name for consistency
        $this->validator->validateHeaderName($name);
        
        $newHeaders = $this->headers->without($name);
        
        return new self($newHeaders->toArray(), $this->validator);
    }

    /**
     * Check if header exists.
     */
    public function has(string $name): bool
    {
        return $this->headers->has($name);
    }

    /**
     * Get header value.
     */
    public function get(string $name): array
    {
        return $this->headers->get($name);
    }

    /**
     * Get first header value.
     */
    public function getLine(string $name): string
    {
        return $this->headers->getLine($name);
    }

    /**
     * Get all headers.
     */
    public function toArray(): array
    {
        return $this->headers->toArray();
    }

    /**
     * Get security statistics.
     */
    public function getSecurityStats(): array
    {
        $headers = $this->headers->toArray();
        $totalSize = 0;
        $totalHeaders = count($headers);
        
        foreach ($headers as $name => $values) {
            $valuesArray = is_array($values) ? $values : [$values];
            $totalSize += strlen($name) + strlen(implode(', ', $valuesArray));
        }

        return [
            'total_headers' => $totalHeaders,
            'total_size_bytes' => $totalSize,
            'security_level' => $this->validator->getConfig()['strict_mode'] ? 'strict' : 'normal',
            'validation_passed' => true,
        ];
    }

    /**
     * Validate all current headers against security rules.
     */
    public function validateAll(): bool
    {
        try {
            $this->validator->validateHeaders($this->headers->toArray());
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Create from existing HeaderCollection with security validation.
     */
    public static function fromHeaderCollection(
        HeaderCollection $headers,
        ?SecurityValidator $validator = null
    ): self {
        return new self($headers->toArray(), $validator);
    }

    /**
     * Get the underlying HeaderCollection (for compatibility).
     */
    public function getHeaderCollection(): HeaderCollection
    {
        return $this->headers;
    }
}