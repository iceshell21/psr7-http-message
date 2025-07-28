<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Trait;

use IceShell21\Psr7HttpMessage\Collection\HeaderCollection;
use IceShell21\Psr7HttpMessage\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * Common functionality for HTTP messages (Request and Response).
 */
trait MessageTrait
{
    private HeaderCollection $headerCollection;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        return $this->cloneWith('protocolVersion', $version);
    }

    public function getHeaders(): array
    {
        return $this->headerCollection->toArray();
    }

    public function hasHeader(string $name): bool
    {
        return $this->headerCollection->has($name);
    }

    public function getHeader(string $name): array
    {
        return $this->headerCollection->get($name);
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        return $this->cloneWithHeaders($this->headerCollection->with($name, $value));
    }

    public function withAddedHeader(string $name, $value): static
    {
        return $this->cloneWithHeaders($this->headerCollection->withAdded($name, $value));
    }

    public function withoutHeader(string $name): static
    {
        return $this->cloneWithHeaders($this->headerCollection->without($name));
    }

    public function getBody(): StreamInterface
    {
        return $this->body ??= new Stream();
    }

    public function withBody(StreamInterface $body): static
    {
        return $this->cloneWith('body', $body);
    }

    private function cloneWith(string $property, mixed $value): static
    {
        if ($this->{$property} === $value) {
            return $this;
        }

        $new = clone $this;
        $new->{$property} = $value;
        return $new;
    }

    private function cloneWithHeaders(HeaderCollection $headers): static
    {
        $new = clone $this;
        $new->headerCollection = $headers;
        return $new;
    }

    private function initializeHeaders(array $headers): void
    {
        // Normalize headers - ensure all values are arrays
        $normalizedHeaders = [];
        foreach ($headers as $name => $value) {
            $normalizedName = mb_strtolower((string) $name, 'UTF-8');
            $normalizedHeaders[$normalizedName] = is_array($value) ? $value : [$value];
        }
        
        $this->headerCollection = new HeaderCollection($normalizedHeaders);
    }
}
