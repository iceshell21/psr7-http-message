<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Tests\Performance;

use IceShell21\Psr7HttpMessage\Performance\ObjectPool;
use IceShell21\Psr7HttpMessage\Request;
use IceShell21\Psr7HttpMessage\Response;
use IceShell21\Psr7HttpMessage\Uri;
use IceShell21\Psr7HttpMessage\Stream;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for Object Pool functionality.
 */
class ObjectPoolTest extends TestCase
{
    private ObjectPool $pool;

    protected function setUp(): void
    {
        $this->pool = new ObjectPool(maxPoolSize: 10, enableMetrics: true);
    }

    public function testRequestPooling(): void
    {
        // Borrow request
        $request1 = $this->pool->borrowRequest();
        $this->assertInstanceOf(Request::class, $request1);

        // Return request
        $this->pool->returnRequest($request1);

        // Borrow again - should get pooled object
        $request2 = $this->pool->borrowRequest();
        $this->assertInstanceOf(Request::class, $request2);

        $stats = $this->pool->getPoolStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('request_pool_size', $stats);
    }

    public function testResponsePooling(): void
    {
        $response1 = $this->pool->borrowResponse();
        $this->assertInstanceOf(Response::class, $response1);

        $this->pool->returnResponse($response1);

        $response2 = $this->pool->borrowResponse();
        $this->assertInstanceOf(Response::class, $response2);

        $stats = $this->pool->getPoolStats();
        $this->assertArrayHasKey('response_pool_size', $stats);
    }

    public function testUriPooling(): void
    {
        $uri1 = $this->pool->borrowUri();
        $this->assertInstanceOf(Uri::class, $uri1);

        $this->pool->returnUri($uri1);

        $uri2 = $this->pool->borrowUri();
        $this->assertInstanceOf(Uri::class, $uri2);

        $stats = $this->pool->getPoolStats();
        $this->assertArrayHasKey('uri_pool_size', $stats);
    }

    public function testStreamPooling(): void
    {
        $stream1 = $this->pool->borrowStream();
        $this->assertInstanceOf(Stream::class, $stream1);

        $this->pool->returnStream($stream1);

        $stream2 = $this->pool->borrowStream();
        $this->assertInstanceOf(Stream::class, $stream2);

        $stats = $this->pool->getPoolStats();
        $this->assertArrayHasKey('stream_pool_size', $stats);
    }

    public function testPoolSizeLimit(): void
    {
        $requests = [];
        
        // Create more objects than pool size
        for ($i = 0; $i < 15; $i++) {
            $requests[] = $this->pool->borrowRequest();
        }

        // Return all objects
        foreach ($requests as $request) {
            $this->pool->returnRequest($request);
        }

        $stats = $this->pool->getPoolStats();
        
        // Pool should not exceed max size
        $this->assertLessThanOrEqual(10, $stats['request_pool_size']);
    }

    public function testPoolStatistics(): void
    {
        $stats = $this->pool->getPoolStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('request_pool_size', $stats);
        $this->assertArrayHasKey('response_pool_size', $stats);
        $this->assertArrayHasKey('uri_pool_size', $stats);
        $this->assertArrayHasKey('stream_pool_size', $stats);
        $this->assertArrayHasKey('max_pool_size', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('peak_memory', $stats);
    }

    public function testClearPools(): void
    {
        // Add objects to pools
        $this->pool->returnRequest(new Request());
        $this->pool->returnResponse(new Response());
        $this->pool->returnUri(new Uri());

        $statsBefore = $this->pool->getPoolStats();
        $this->assertGreaterThan(0, $statsBefore['request_pool_size'] + $statsBefore['response_pool_size'] + $statsBefore['uri_pool_size']);

        // Clear pools
        $this->pool->clearPools();

        $statsAfter = $this->pool->getPoolStats();
        $this->assertEquals(0, $statsAfter['request_pool_size']);
        $this->assertEquals(0, $statsAfter['response_pool_size']);
        $this->assertEquals(0, $statsAfter['uri_pool_size']);
    }

    public function testPoolMetrics(): void
    {
        $pool = new ObjectPool(maxPoolSize: 5, enableMetrics: true);
        
        // Borrow and return to generate metrics
        $request = $pool->borrowRequest();
        $pool->returnRequest($request);

        $stats = $pool->getPoolStats();
        $this->assertIsArray($stats);
        
        // Metrics should be tracked
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertIsNumeric($stats['memory_usage']);
    }

    public function testPoolWithoutMetrics(): void
    {
        $pool = new ObjectPool(maxPoolSize: 5, enableMetrics: false);
        
        $request = $pool->borrowRequest();
        $this->assertInstanceOf(Request::class, $request);
        
        $pool->returnRequest($request);
        
        $stats = $pool->getPoolStats();
        $this->assertIsArray($stats);
    }
}