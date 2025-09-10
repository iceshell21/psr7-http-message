<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Security;

use IceShell21\Psr7HttpMessage\Performance\OptimizedStream;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Security-enhanced stream with memory limits and content validation.
 * Protects against DoS attacks and malicious content.
 */
final class SecureStream implements StreamInterface
{
    private const DEFAULT_MAX_SIZE = 134217728; // 128MB
    private const SCAN_CHUNK_SIZE = 8192; // 8KB
    
    private OptimizedStream $stream;
    private int $maxSize;
    private bool $contentScanning;
    private array $dangerousPatterns;

    public function __construct(
        mixed $stream = '',
        int $maxSize = self::DEFAULT_MAX_SIZE,
        bool $contentScanning = true
    ) {
        $this->maxSize = $maxSize;
        $this->contentScanning = $contentScanning;
        $this->dangerousPatterns = $this->getDangerousPatterns();
        
        $this->stream = new OptimizedStream($stream);
        
        if ($contentScanning) {
            $this->scanContent();
        }
    }

    public function __toString(): string
    {
        try {
            $content = $this->stream->__toString();
            
            if ($this->contentScanning) {
                $this->validateContent($content);
            }
            
            return $content;
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        $this->stream->close();
    }

    public function detach(): mixed
    {
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        $size = $this->stream->getSize();
        
        if ($size !== null && $size > $this->maxSize) {
            throw new RuntimeException('Stream size exceeds security limit');
        }
        
        return $size;
    }

    public function tell(): int
    {
        return $this->stream->tell();
    }

    public function eof(): bool
    {
        return $this->stream->eof();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->stream->rewind();
    }

    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function write(string $string): int
    {
        // Check size limits before writing
        $currentSize = $this->getSize() ?? 0;
        if ($currentSize + strlen($string) > $this->maxSize) {
            throw new RuntimeException('Write would exceed security size limit');
        }

        // Scan content for threats if enabled
        if ($this->contentScanning) {
            $this->validateContent($string);
        }

        return $this->stream->write($string);
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function read(int $length): string
    {
        // Limit read length to prevent DoS
        $maxRead = min($length, self::SCAN_CHUNK_SIZE);
        
        $content = $this->stream->read($maxRead);
        
        if ($this->contentScanning && $content !== '') {
            $this->validateContent($content);
        }
        
        return $content;
    }

    public function getContents(): string
    {
        $content = $this->stream->getContents();
        
        if ($this->contentScanning) {
            $this->validateContent($content);
        }
        
        return $content;
    }

    public function getMetadata(?string $key = null): mixed
    {
        $metadata = $this->stream->getMetadata($key);
        
        // Add security information
        if ($key === null) {
            $metadata['security'] = [
                'max_size' => $this->maxSize,
                'content_scanning' => $this->contentScanning,
                'current_size' => $this->getSize(),
            ];
        } elseif ($key === 'security') {
            return [
                'max_size' => $this->maxSize,
                'content_scanning' => $this->contentScanning,
                'current_size' => $this->getSize(),
            ];
        }
        
        return $metadata;
    }

    /**
     * Scan stream content for security threats.
     */
    private function scanContent(): void
    {
        if (!$this->stream->isSeekable()) {
            return; // Cannot scan non-seekable streams
        }

        $originalPosition = $this->stream->tell();
        $this->stream->rewind();

        $scannedSize = 0;
        while (!$this->stream->eof() && $scannedSize < $this->maxSize) {
            $chunk = $this->stream->read(self::SCAN_CHUNK_SIZE);
            if ($chunk === '') {
                break;
            }

            $this->validateContent($chunk);
            $scannedSize += strlen($chunk);
        }

        $this->stream->seek($originalPosition);
    }

    /**
     * Validate content for dangerous patterns.
     */
    private function validateContent(string $content): void
    {
        foreach ($this->dangerousPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                throw new RuntimeException("Security threat detected: $description");
            }
        }

        // Check for excessively long lines (potential DoS)
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (strlen($line) > 10000) { // 10KB per line limit
                throw new RuntimeException('Line too long (potential DoS attack)');
            }
        }
    }

    /**
     * Get dangerous content patterns.
     */
    private function getDangerousPatterns(): array
    {
        return [
            '/<script[^>]*>/i' => 'Script tag detected',
            '/javascript:/i' => 'JavaScript protocol detected',
            '/vbscript:/i' => 'VBScript protocol detected',
            '/data:.*base64/i' => 'Base64 data URI detected',
            '/eval\s*\(/i' => 'Eval function detected',
            '/exec\s*\(/i' => 'Exec function detected',
            '/system\s*\(/i' => 'System function detected',
            '/shell_exec\s*\(/i' => 'Shell exec function detected',
            '/`[^`]*`/' => 'Shell command detected',
            '/<iframe[^>]*>/i' => 'IFrame tag detected',
            '/<object[^>]*>/i' => 'Object tag detected',
            '/<embed[^>]*>/i' => 'Embed tag detected',
            '/on\w+\s*=/i' => 'Event handler detected',
            '/expression\s*\(/i' => 'CSS expression detected',
        ];
    }

    /**
     * Get security statistics.
     */
    public function getSecurityStats(): array
    {
        return [
            'max_size' => $this->maxSize,
            'current_size' => $this->getSize(),
            'content_scanning' => $this->contentScanning,
            'patterns_checked' => count($this->dangerousPatterns),
            'security_level' => 'high',
        ];
    }

    /**
     * Create secure stream from file.
     */
    public static function fromFile(
        string $filename,
        string $mode = 'r',
        int $maxSize = self::DEFAULT_MAX_SIZE
    ): self {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }

        if (filesize($filename) > $maxSize) {
            throw new RuntimeException("File too large: $filename");
        }

        $resource = fopen($filename, $mode);
        if ($resource === false) {
            throw new RuntimeException("Cannot open file: $filename");
        }

        return new self($resource, $maxSize);
    }
}