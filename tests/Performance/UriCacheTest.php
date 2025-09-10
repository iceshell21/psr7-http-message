<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Performance;

use IceShell21\Psr7HttpMessage\Performance\UriCache;
use IceShell21\Psr7HttpMessage\Uri;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for URI Cache functionality.
 */
class UriCacheTest extends TestCase
{
    private UriCache $cache;

    protected function setUp(): void
    {
        $this->cache = new UriCache(maxSize: 100);
    }

    public function testUriCaching(): void
    {
        $uri = 'https://example.com/api/users';
        
        // First call should miss cache
        $uri1 = $this->cache->getOrCreate($uri);
        $this->assertInstanceOf(Uri::class, $uri1);
        
        $stats = $this->cache->getStats();
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(0, $stats['hits']);

        // Second call should hit cache
        $uri2 = $this->cache->getOrCreate($uri);
        $this->assertInstanceOf(Uri::class, $uri2);
        
        $stats = $this->cache->getStats();
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(1, $stats['hits']);
    }

    public function testCacheHitRate(): void
    {
        $uris = [
            'https://api.example.com/v1/users',
            'https://api.example.com/v1/posts',
            'https://api.example.com/v1/comments',
        ];

        // Fill cache
        foreach ($uris as $uri) {
            $this->cache->getOrCreate($uri);
        }

        // Access cached URIs
        foreach ($uris as $uri) {
            $this->cache->getOrCreate($uri);
        }

        $stats = $this->cache->getStats();
        $this->assertEquals(50.0, $stats['hit_rate']); // 3 hits out of 6 total
    }

    public function testCacheSizeLimit(): void
    {
        $cache = new UriCache(maxSize: 3);
        
        $uris = [
            'https://example1.com',
            'https://example2.com',
            'https://example3.com',
            'https://example4.com', // This should evict the first one
        ];

        foreach ($uris as $uri) {
            $cache->getOrCreate($uri);
        }

        $stats = $cache->getStats();
        $this->assertEquals(3, $stats['size']); // Should not exceed max size
        
        // First URI should be evicted (LRU)
        $this->assertFalse($cache->has('https://example1.com'));
        $this->assertTrue($cache->has('https://example4.com'));
    }

    public function testCacheHasMethod(): void
    {
        $uri = 'https://test.example.com';
        
        $this->assertFalse($this->cache->has($uri));
        
        $this->cache->getOrCreate($uri);
        
        $this->assertTrue($this->cache->has($uri));
    }

    public function testCacheStats(): void
    {
        $stats = $this->cache->getStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('size', $stats);
        $this->assertArrayHasKey('max_size', $stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
    }

    public function testCacheClear(): void
    {
        // Add some URIs
        $this->cache->getOrCreate('https://example1.com');
        $this->cache->getOrCreate('https://example2.com');
        
        $statsBefore = $this->cache->getStats();
        $this->assertGreaterThan(0, $statsBefore['size']);
        
        // Clear cache
        $this->cache->clear();
        
        $statsAfter = $this->cache->getStats();
        $this->assertEquals(0, $statsAfter['size']);
        $this->assertEquals(0, $statsAfter['hits']);
        $this->assertEquals(0, $statsAfter['misses']);
    }

    public function testCacheEfficiency(): void
    {
        // No operations yet
        $this->assertEquals(0.0, $this->cache->getEfficiency());
        
        // Add some cache hits and misses
        $this->cache->getOrCreate('https://example.com'); // miss
        $this->cache->getOrCreate('https://example.com'); // hit
        
        $this->assertEquals(0.5, $this->cache->getEfficiency()); // 1 hit out of 2 total
    }

    public function testPreWarmCache(): void
    {
        $commonUris = [
            'https://api.example.com/users',
            'https://api.example.com/posts',
            'https://api.example.com/comments',
        ];
        
        $this->cache->preWarm($commonUris);
        
        $stats = $this->cache->getStats();
        $this->assertEquals(3, $stats['size']);
        $this->assertEquals(3, $stats['misses']); // Pre-warming counts as misses
        
        // Now accessing should hit cache
        foreach ($commonUris as $uri) {
            $this->assertTrue($this->cache->has($uri));
        }
    }

    public function testMostAccessedUris(): void
    {
        $uris = [
            'https://example1.com',
            'https://example2.com',
            'https://example3.com',
        ];
        
        foreach ($uris as $uri) {
            $this->cache->getOrCreate($uri);
        }
        
        $mostAccessed = $this->cache->getMostAccessed(2);
        $this->assertIsArray($mostAccessed);
        $this->assertCount(2, $mostAccessed);
    }

    public function testMemoryUsageEstimation(): void
    {
        $this->cache->getOrCreate('https://example.com/very/long/path/to/resource');
        
        $stats = $this->cache->getStats();
        $this->assertGreaterThan(0, $stats['memory_usage']);
        $this->assertIsNumeric($stats['memory_usage']);
    }

    public function testLRUEviction(): void
    {
        $cache = new UriCache(maxSize: 2);
        
        // Fill cache to capacity
        $cache->getOrCreate('uri1');
        $cache->getOrCreate('uri2');
        
        // Access first URI to make it more recent
        $cache->getOrCreate('uri1');
        
        // Add third URI - should evict uri2 (least recently used)
        $cache->getOrCreate('uri3');
        
        $this->assertTrue($cache->has('uri1')); // Should still be cached
        $this->assertFalse($cache->has('uri2')); // Should be evicted
        $this->assertTrue($cache->has('uri3')); // Should be cached
    }
}