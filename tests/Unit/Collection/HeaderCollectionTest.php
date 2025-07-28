<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Unit\Collection;

use IceShell21\Psr7HttpMessage\Collection\HeaderCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IceShell21\Psr7HttpMessage\Collection\HeaderCollection
 */
final class HeaderCollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $headers = ['content-type' => ['application/json']];
        $collection = new HeaderCollection($headers);
        
        $this->assertSame(['application/json'], $collection->get('content-type'));
    }

    public function testGetCaseInsensitive(): void
    {
        $collection = new HeaderCollection(['content-type' => ['application/json']]);
        
        $this->assertSame(['application/json'], $collection->get('Content-Type'));
        $this->assertSame(['application/json'], $collection->get('CONTENT-TYPE'));
        $this->assertSame(['application/json'], $collection->get('content-type'));
    }

    public function testGetNonExistent(): void
    {
        $collection = new HeaderCollection();
        
        $this->assertSame([], $collection->get('non-existent'));
    }

    public function testHas(): void
    {
        $collection = new HeaderCollection(['content-type' => ['application/json']]);
        
        $this->assertTrue($collection->has('Content-Type'));
        $this->assertTrue($collection->has('content-type'));
        $this->assertFalse($collection->has('non-existent'));
    }

    public function testWith(): void
    {
        $collection = new HeaderCollection();
        $newCollection = $collection->with('Content-Type', 'application/json');
        
        $this->assertFalse($collection->has('Content-Type'));
        $this->assertTrue($newCollection->has('Content-Type'));
        $this->assertSame(['application/json'], $newCollection->get('Content-Type'));
    }

    public function testWithArray(): void
    {
        $collection = new HeaderCollection();
        $newCollection = $collection->with('Accept', ['application/json', 'text/html']);
        
        $this->assertSame(['application/json', 'text/html'], $newCollection->get('Accept'));
    }

    public function testWithAdded(): void
    {
        $collection = new HeaderCollection(['accept' => ['application/json']]);
        $newCollection = $collection->withAdded('Accept', 'text/html');
        
        $this->assertSame(['application/json'], $collection->get('Accept'));
        $this->assertSame(['application/json', 'text/html'], $newCollection->get('Accept'));
    }

    public function testWithAddedNew(): void
    {
        $collection = new HeaderCollection();
        $newCollection = $collection->withAdded('Content-Type', 'application/json');
        
        $this->assertSame(['application/json'], $newCollection->get('Content-Type'));
    }

    public function testWithout(): void
    {
        $collection = new HeaderCollection(['content-type' => ['application/json']]);
        $newCollection = $collection->without('Content-Type');
        
        $this->assertTrue($collection->has('Content-Type'));
        $this->assertFalse($newCollection->has('Content-Type'));
    }

    public function testToArray(): void
    {
        $headers = ['content-type' => ['application/json'], 'accept' => ['text/html']];
        $collection = new HeaderCollection($headers);
        
        $this->assertSame($headers, $collection->toArray());
    }
}
