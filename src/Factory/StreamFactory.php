<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Factory;

use IceShell21\Psr7HttpMessage\Stream;
use InvalidArgumentException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * PSR-17 HTTP Stream Factory implementation.
 */
final class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("File does not exist: $filename");
        }

        $resource = fopen($filename, $mode);
        if ($resource === false) {
            throw new RuntimeException("Cannot open file: $filename");
        }

        return new Stream($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Invalid resource provided');
        }

        return new Stream($resource);
    }
}
