<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Collection;

/**
 * Immutable collection for HTTP headers with case-insensitive handling.
 */
final class HeaderCollection
{
    /**
     * @param array<string, array<string>> $headers
     */
    public function __construct(
        private array $headers = []
    ) {}

    /**
     * Get header values by name (case-insensitive).
     *
     * @return array<string>
     */
    public function get(string $name): array
    {
        $normalized = mb_strtolower($name, 'UTF-8');
        return $this->headers[$normalized] ?? [];
    }

    /**
     * Get header values as a comma-separated string.
     */
    public function getLine(string $name): string
    {
        return implode(', ', $this->get($name));
    }

    /**
     * Check if header exists (case-insensitive).
     */
    public function has(string $name): bool
    {
        return isset($this->headers[mb_strtolower($name, 'UTF-8')]);
    }

    /**
     * Return new instance with header set to value(s).
     *
     * @param string|array<string> $value
     */
    public function with(string $name, string|array $value): self
    {
        $headers = $this->headers;
        $headers[mb_strtolower($name, 'UTF-8')] = is_array($value) ? $value : [$value];
        return new self($headers);
    }

    /**
     * Return new instance with header value(s) added.
     *
     * @param string|array<string> $value
     */
    public function withAdded(string $name, string|array $value): self
    {
        $normalizedName = mb_strtolower($name, 'UTF-8');
        $values = is_array($value) ? $value : [$value];
        $headers = $this->headers;
        
        $headers[$normalizedName] = isset($headers[$normalizedName]) 
            ? [...$this->get($name), ...$values]
            : $values;
            
        return new self($headers);
    }

    /**
     * Alias for withAdded() to match SecureHeaderCollection API.
     *
     * @param string|array<string> $value
     */
    public function withAddedValue(string $name, string|array $value): self
    {
        return $this->withAdded($name, $value);
    }

    /**
     * Return new instance without the specified header.
     */
    public function without(string $name): self
    {
        $headers = $this->headers;
        unset($headers[mb_strtolower($name, 'UTF-8')]);
        return new self($headers);
    }

    /**
     * Get all headers as associative array.
     *
     * @return array<string, array<string>>
     */
    public function toArray(): array
    {
        return $this->headers;
    }
}
