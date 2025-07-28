<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Uploaded file implementation with proper error handling.
 */
final class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    public function __construct(
        private StreamInterface|string $streamOrFile,
        private ?int $size = null,
        private int $error = UPLOAD_ERR_OK,
        private ?string $clientFilename = null,
        private ?string $clientMediaType = null
    ) {
        if ($this->error !== UPLOAD_ERR_OK) {
            return;
        }

        if (is_string($streamOrFile) && !is_file($streamOrFile)) {
            throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }

    public function getStream(): StreamInterface
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has been moved');
        }

        if ($this->streamOrFile instanceof StreamInterface) {
            return $this->streamOrFile;
        }

        return new Stream(fopen($this->streamOrFile, 'r'));
    }

    public function moveTo(string $targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot move file due to upload error');
        }

        if ($this->moved) {
            throw new RuntimeException('File has already been moved');
        }

        if (empty($targetPath)) {
            throw new InvalidArgumentException('Target path cannot be empty');
        }

        $targetDirectory = dirname($targetPath);
        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new RuntimeException('Target directory is not writable');
        }

        if ($this->streamOrFile instanceof StreamInterface) {
            $this->moveStreamToTarget($targetPath);
        } else {
            $this->moveFileToTarget($targetPath);
        }

        $this->moved = true;
    }

    private function moveStreamToTarget(string $targetPath): void
    {
        $stream = $this->getStream();
        $target = fopen($targetPath, 'w');
        
        if ($target === false) {
            throw new RuntimeException('Cannot open target file for writing');
        }

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            fwrite($target, $stream->read(8192));
        }

        fclose($target);
    }

    private function moveFileToTarget(string $targetPath): void
    {
        if (PHP_SAPI === 'cli') {
            if (!rename($this->streamOrFile, $targetPath)) {
                throw new RuntimeException('Error moving uploaded file');
            }
        } else {
            if (!move_uploaded_file($this->streamOrFile, $targetPath)) {
                throw new RuntimeException('Error moving uploaded file');
            }
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
