<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

/**
 * Memory-efficient stream implementation with proper resource management.
 */
final class Stream implements StreamInterface
{
    private ?int $size = null;
    private bool $seekable;
    private bool $readable;
    private bool $writable;
    private mixed $stream;

    public function __construct(mixed $stream = '')
    {
        $this->stream = $this->createResource($stream);
        $this->initializeStreamProperties();
    }

    private function createResource(mixed $stream): mixed
    {
        if (is_resource($stream)) {
            return $stream;
        }

        $content = match (true) {
            is_string($stream) => $stream,
            is_null($stream) => '',
            default => (string) $stream
        };

        $resource = fopen('php://temp', 'w+b');
        if ($resource === false) {
            throw new RuntimeException('Failed to create temp stream');
        }

        if ($content !== '') {
            if (fwrite($resource, $content) === false) {
                fclose($resource);
                throw new RuntimeException('Failed to write to stream');
            }
            rewind($resource);
        }

        return $resource;
    }

    private function initializeStreamProperties(): void
    {
        $meta = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'] ?? false;
        $this->readable = $this->isReadableMode($meta['mode'] ?? '');
        $this->writable = $this->isWritableMode($meta['mode'] ?? '');
    }

    private function isReadableMode(string $mode): bool
    {
        return str_contains($mode, 'r') || str_contains($mode, '+');
    }

    private function isWritableMode(string $mode): bool
    {
        return str_contains($mode, 'w') || str_contains($mode, 'a') || str_contains($mode, '+');
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }
            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if (isset($this->stream)) {
            fclose($this->stream);
            $this->detach();
        }
    }

    public function detach(): mixed
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size = null;
        
        return $result;
    }

    public function getSize(): ?int
    {
        if (!isset($this->stream)) {
            return null;
        }

        if ($this->size !== null) {
            return $this->size;
        }

        $stats = fstat($this->stream);
        $this->size = $stats['size'] ?? null;
        return $this->size;
    }

    public function tell(): int
    {
        $this->assertStreamIsAvailable();
        
        $result = ftell($this->stream);
        if ($result === false) {
            throw new RuntimeException('Unable to determine stream position');
        }
        
        return $result;
    }

    public function eof(): bool
    {
        return !isset($this->stream) || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->assertStreamIsAvailable();
        $this->assertStreamIsSeekable();
        
        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek to stream position');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write(string $string): int
    {
        $this->assertStreamIsAvailable();
        $this->assertStreamIsWritable();
        
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }
        
        return $result;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        $this->assertStreamIsAvailable();
        $this->assertStreamIsReadable();
        
        $result = fread($this->stream, $length);
        if ($result === false) {
            throw new RuntimeException('Unable to read from stream');
        }
        
        return $result;
    }

    public function getContents(): string
    {
        $this->assertStreamIsAvailable();
        $this->assertStreamIsReadable();
        
        $result = stream_get_contents($this->stream);
        if ($result === false) {
            throw new RuntimeException('Unable to read stream contents');
        }
        
        return $result;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        }
        
        $meta = stream_get_meta_data($this->stream);
        return $key === null ? $meta : ($meta[$key] ?? null);
    }

    private function assertStreamIsAvailable(): void
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }
    }

    private function assertStreamIsSeekable(): void
    {
        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }
    }

    private function assertStreamIsWritable(): void
    {
        if (!$this->writable) {
            throw new RuntimeException('Stream is not writable');
        }
    }

    private function assertStreamIsReadable(): void
    {
        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }
    }
}
