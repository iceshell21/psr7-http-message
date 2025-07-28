<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Unit;

use IceShell21\Psr7HttpMessage\Stream;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \IceShell21\Psr7HttpMessage\Stream
 */
final class StreamTest extends TestCase
{
    public function testConstructorWithString(): void
    {
        $stream = new Stream('test content');
        
        $this->assertSame('test content', (string) $stream);
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
    }

    public function testConstructorWithResource(): void
    {
        $resource = fopen('php://temp', 'w+b');
        fwrite($resource, 'test content');
        rewind($resource);
        
        $stream = new Stream($resource);
        
        $this->assertSame('test content', (string) $stream);
    }

    public function testWrite(): void
    {
        $stream = new Stream();
        $written = $stream->write('hello world');
        
        $this->assertSame(11, $written);
        $this->assertSame('hello world', (string) $stream);
    }

    public function testRead(): void
    {
        $stream = new Stream('hello world');
        $content = $stream->read(5);
        
        $this->assertSame('hello', $content);
    }

    public function testSeek(): void
    {
        $stream = new Stream('hello world');
        $stream->seek(6);
        
        $this->assertSame('world', $stream->read(5));
    }

    public function testRewind(): void
    {
        $stream = new Stream('hello world');
        $stream->read(5);
        $stream->rewind();
        
        $this->assertSame('hello', $stream->read(5));
    }

    public function testGetSize(): void
    {
        $stream = new Stream('hello world');
        
        $this->assertSame(11, $stream->getSize());
    }

    public function testTell(): void
    {
        $stream = new Stream('hello world');
        $stream->read(5);
        
        $this->assertSame(5, $stream->tell());
    }

    public function testEof(): void
    {
        $stream = new Stream('test');
        
        $this->assertFalse($stream->eof());
        
        $stream->read(4);
        $stream->read(1); // Try to read beyond end
        
        $this->assertTrue($stream->eof());
    }

    public function testClose(): void
    {
        $stream = new Stream('test');
        $stream->close();
        
        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }

    public function testDetach(): void
    {
        $stream = new Stream('test');
        $resource = $stream->detach();
        
        $this->assertIsResource($resource);
        $this->assertNull($stream->getSize());
    }

    public function testGetContents(): void
    {
        $stream = new Stream('hello world');
        $stream->seek(6);
        
        $this->assertSame('world', $stream->getContents());
    }

    public function testGetMetadata(): void
    {
        $stream = new Stream('test');
        $metadata = $stream->getMetadata();
        
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('mode', $metadata);
    }

    public function testGetMetadataWithKey(): void
    {
        $stream = new Stream('test');
        $mode = $stream->getMetadata('mode');
        
        $this->assertIsString($mode);
    }
}
