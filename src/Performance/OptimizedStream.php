<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Performance;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

/**
 * High-performance, memory-efficient stream implementation.
 * Optimized for handling large files with minimal memory footprint.
 */
final class OptimizedStream implements StreamInterface
{
    private const DEFAULT_CHUNK_SIZE = 8192; // 8KB chunks
    private const LARGE_FILE_THRESHOLD = 2097152; // 2MB
    private const MAX_MEMORY_LIMIT = 134217728; // 128MB
    
    private ?int $size = null;
    private bool $seekable;
    private bool $readable;
    private bool $writable;
    private mixed $stream;
    private int $chunkSize;
    private bool $isLargeFile = false;

    public function __construct(
        mixed $stream = '',
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ) {
        $this->chunkSize = $chunkSize;
        $this->stream = $this->createResource($stream);
        $this->initializeStreamProperties();
        $this->detectLargeFile();
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

        // For large content, use file-based temp stream
        if (strlen($content) > self::LARGE_FILE_THRESHOLD) {
            $resource = fopen('php://temp/maxmemory:' . self::LARGE_FILE_THRESHOLD, 'w+b');
        } else {
            $resource = fopen('php://temp', 'w+b');
        }

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

    private function detectLargeFile(): void
    {
        $size = $this->getSize();
        $this->isLargeFile = $size !== null && $size > self::LARGE_FILE_THRESHOLD;
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
            if ($this->isLargeFile) {
                // For large files, use chunked reading to avoid memory issues
                return $this->readInChunks();
            }

            if ($this->isSeekable()) {
                $this->seek(0);
            }
            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Read large files in chunks to minimize memory usage.
     */
    private function readInChunks(): string
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Cannot read large non-seekable stream');
        }

        $originalPosition = $this->tell();
        $this->seek(0);
        
        $result = '';
        while (!$this->eof()) {
            $chunk = $this->read($this->chunkSize);
            if ($chunk === '') {
                break;
            }
            $result .= $chunk;
            
            // Memory safety check
            if (strlen($result) > self::MAX_MEMORY_LIMIT) {
                throw new RuntimeException('Stream content exceeds memory limit');
            }
        }
        
        $this->seek($originalPosition);
        return $result;
    }

    /**
     * Copy stream content to another stream with zero-copy optimization.
     */
    public function copyTo(StreamInterface $dest): int
    {
        // Use stream_copy_to_stream for maximum efficiency when possible
        if ($this->isResource() && $dest instanceof self && $dest->isResource()) {
            $byteCopied = stream_copy_to_stream($this->stream, $dest->stream);
            if ($byteCopied === false) {
                throw new RuntimeException('Failed to copy stream');
            }
            return $byteCopied;
        }

        return $this->chunkedCopy($dest);
    }

    /**
     * Copy using chunked reading for memory efficiency.
     */
    private function chunkedCopy(StreamInterface $dest): int
    {
        $totalBytes = 0;
        
        while (!$this->eof()) {
            $chunk = $this->read($this->chunkSize);
            if ($chunk === '') {
                break;
            }
            
            $bytesWritten = $dest->write($chunk);
            $totalBytes += $bytesWritten;
        }
        
        return $totalBytes;
    }

    private function isResource(): bool
    {
        return is_resource($this->stream);
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
        if ($stats !== false) {
            $this->size = $stats['size'] ?? null;
        }
        
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
        
        // Memory safety check
        $currentSize = $this->getSize() ?? 0;
        if ($currentSize + strlen($string) > self::MAX_MEMORY_LIMIT) {
            throw new RuntimeException('Write would exceed memory limit');
        }
        
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }
        
        // Reset cached size
        $this->size = null;
        
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
        
        // Adjust length for large file handling
        if ($this->isLargeFile && $length > $this->chunkSize) {
            $length = $this->chunkSize;
        }
        
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
        
        if ($this->isLargeFile) {
            return $this->readInChunks();
        }
        
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
        
        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    /**
     * Get stream performance statistics.
     */
    public function getPerformanceStats(): array
    {
        return [
            'is_large_file' => $this->isLargeFile,
            'chunk_size' => $this->chunkSize,
            'size' => $this->getSize(),
            'position' => $this->tell(),
            'seekable' => $this->seekable,
            'readable' => $this->readable,
            'writable' => $this->writable,
        ];
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

    private function assertStreamIsReadable(): void
    {
        if (!$this->readable) {
            throw new RuntimeException('Cannot read from non-readable stream');
        }
    }

    private function assertStreamIsWritable(): void
    {
        if (!$this->writable) {
            throw new RuntimeException('Cannot write to non-writable stream');
        }
    }
}