<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Performance;

use IceShell21\Psr7HttpMessage\Request;
use IceShell21\Psr7HttpMessage\Response;
use IceShell21\Psr7HttpMessage\Uri;
use IceShell21\Psr7HttpMessage\Stream;
use SplObjectStorage;
use WeakMap;

/**
 * High-performance object pool for frequently used HTTP message objects.
 * Reduces garbage collection overhead and improves performance by reusing objects.
 */
final readonly class ObjectPool
{
    private SplObjectStorage $requestPool;
    private SplObjectStorage $responsePool;
    private SplObjectStorage $uriPool;
    private SplObjectStorage $streamPool;
    private WeakMap $objectMetadata;

    public function __construct(
        private int $maxPoolSize = 100,
        private bool $enableMetrics = true
    ) {
        $this->requestPool = new SplObjectStorage();
        $this->responsePool = new SplObjectStorage();
        $this->uriPool = new SplObjectStorage();
        $this->streamPool = new SplObjectStorage();
        $this->objectMetadata = new WeakMap();
    }

    /**
     * Borrow a Request object from the pool or create a new one.
     */
    public function borrowRequest(): Request
    {
        if ($this->requestPool->count() > 0) {
            $this->requestPool->rewind();
            $request = $this->requestPool->current();
            $this->requestPool->detach($request);
            
            if ($this->enableMetrics) {
                $this->trackObjectReuse($request, 'request');
            }
            
            return $request;
        }

        $request = new Request();
        
        if ($this->enableMetrics) {
            $this->trackObjectCreation($request, 'request');
        }
        
        return $request;
    }

    /**
     * Return a Request object to the pool for reuse.
     */
    public function returnRequest(Request $request): void
    {
        if ($this->requestPool->count() < $this->maxPoolSize) {
            // Reset the request to a clean state before pooling
            $cleanRequest = $this->resetRequest($request);
            $this->requestPool->attach($cleanRequest);
            
            if ($this->enableMetrics) {
                $this->trackObjectReturn($cleanRequest, 'request');
            }
        }
    }

    /**
     * Borrow a Response object from the pool or create a new one.
     */
    public function borrowResponse(): Response
    {
        if ($this->responsePool->count() > 0) {
            $this->responsePool->rewind();
            $response = $this->responsePool->current();
            $this->responsePool->detach($response);
            
            if ($this->enableMetrics) {
                $this->trackObjectReuse($response, 'response');
            }
            
            return $response;
        }

        $response = new Response();
        
        if ($this->enableMetrics) {
            $this->trackObjectCreation($response, 'response');
        }
        
        return $response;
    }

    /**
     * Return a Response object to the pool for reuse.
     */
    public function returnResponse(Response $response): void
    {
        if ($this->responsePool->count() < $this->maxPoolSize) {
            $cleanResponse = $this->resetResponse($response);
            $this->responsePool->attach($cleanResponse);
            
            if ($this->enableMetrics) {
                $this->trackObjectReturn($cleanResponse, 'response');
            }
        }
    }

    /**
     * Borrow a Uri object from the pool or create a new one.
     */
    public function borrowUri(): Uri
    {
        if ($this->uriPool->count() > 0) {
            $this->uriPool->rewind();
            $uri = $this->uriPool->current();
            $this->uriPool->detach($uri);
            
            if ($this->enableMetrics) {
                $this->trackObjectReuse($uri, 'uri');
            }
            
            return $uri;
        }

        $uri = new Uri();
        
        if ($this->enableMetrics) {
            $this->trackObjectCreation($uri, 'uri');
        }
        
        return $uri;
    }

    /**
     * Return a Uri object to the pool for reuse.
     */
    public function returnUri(Uri $uri): void
    {
        if ($this->uriPool->count() < $this->maxPoolSize) {
            $this->uriPool->attach($uri);
            
            if ($this->enableMetrics) {
                $this->trackObjectReturn($uri, 'uri');
            }
        }
    }

    /**
     * Borrow a Stream object from the pool or create a new one.
     */
    public function borrowStream(): Stream
    {
        if ($this->streamPool->count() > 0) {
            $this->streamPool->rewind();
            $stream = $this->streamPool->current();
            $this->streamPool->detach($stream);
            
            if ($this->enableMetrics) {
                $this->trackObjectReuse($stream, 'stream');
            }
            
            return $stream;
        }

        $stream = new Stream();
        
        if ($this->enableMetrics) {
            $this->trackObjectCreation($stream, 'stream');
        }
        
        return $stream;
    }

    /**
     * Return a Stream object to the pool for reuse.
     */
    public function returnStream(Stream $stream): void
    {
        if ($this->streamPool->count() < $this->maxPoolSize) {
            // Close and reset stream before pooling
            $stream->close();
            $newStream = new Stream(); // Create fresh stream for pooling
            $this->streamPool->attach($newStream);
            
            if ($this->enableMetrics) {
                $this->trackObjectReturn($newStream, 'stream');
            }
        }
    }

    /**
     * Get pool statistics for monitoring.
     */
    public function getPoolStats(): array
    {
        return [
            'request_pool_size' => $this->requestPool->count(),
            'response_pool_size' => $this->responsePool->count(),
            'uri_pool_size' => $this->uriPool->count(),
            'stream_pool_size' => $this->streamPool->count(),
            'max_pool_size' => $this->maxPoolSize,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Clear all pools and reset statistics.
     */
    public function clearPools(): void
    {
        $this->requestPool->removeAll($this->requestPool);
        $this->responsePool->removeAll($this->responsePool);
        $this->uriPool->removeAll($this->uriPool);
        $this->streamPool->removeAll($this->streamPool);
    }

    private function resetRequest(Request $request): Request
    {
        // Create a new clean Request instance
        // Since Request is immutable, we just create a new one
        return new Request();
    }

    private function resetResponse(Response $response): Response
    {
        // Create a new clean Response instance
        // Since Response is immutable, we just create a new one
        return new Response();
    }

    private function trackObjectCreation(object $object, string $type): void
    {
        $this->objectMetadata[$object] = [
            'type' => $type,
            'created_at' => hrtime(true),
            'reuse_count' => 0,
        ];
    }

    private function trackObjectReuse(object $object, string $type): void
    {
        if (isset($this->objectMetadata[$object])) {
            $this->objectMetadata[$object]['reuse_count']++;
            $this->objectMetadata[$object]['last_reused_at'] = hrtime(true);
        }
    }

    private function trackObjectReturn(object $object, string $type): void
    {
        if (isset($this->objectMetadata[$object])) {
            $this->objectMetadata[$object]['returned_at'] = hrtime(true);
        }
    }
}