<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Performance;

use IceShell21\Psr7HttpMessage\Uri;
use Psr\Http\Message\UriInterface;

/**
 * High-performance URI cache to avoid redundant parsing of commonly used URIs.
 * Uses LRU eviction policy to maintain optimal memory usage.
 */
final class UriCache
{
    private array $cache = [];
    private array $accessOrder = [];
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        private readonly int $maxSize = 1000
    ) {}

    /**
     * Get URI from cache or create and cache new one.
     */
    public function getOrCreate(string $uri): UriInterface
    {
        // Use URI string as cache key
        $key = $uri;

        if (isset($this->cache[$key])) {
            $this->hits++;
            $this->updateAccessOrder($key);
            return $this->cache[$key];
        }

        $this->misses++;
        $uriObject = new Uri($uri);
        $this->store($key, $uriObject);
        
        return $uriObject;
    }

    /**
     * Store URI in cache with LRU eviction.
     */
    private function store(string $key, UriInterface $uri): void
    {
        // If cache is full, remove least recently used item
        if (count($this->cache) >= $this->maxSize) {
            $lruKey = array_key_first($this->accessOrder);
            unset($this->cache[$lruKey], $this->accessOrder[$lruKey]);
        }

        $this->cache[$key] = $uri;
        $this->updateAccessOrder($key);
    }

    /**
     * Update access order for LRU tracking.
     */
    private function updateAccessOrder(string $key): void
    {
        // Remove from current position
        unset($this->accessOrder[$key]);
        
        // Add to end (most recent)
        $this->accessOrder[$key] = true;
    }

    /**
     * Check if URI is cached.
     */
    public function has(string $uri): bool
    {
        return isset($this->cache[$uri]);
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        $total = $this->hits + $this->misses;
        $hitRate = $total > 0 ? ($this->hits / $total) * 100 : 0;

        return [
            'size' => count($this->cache),
            'max_size' => $this->maxSize,
            'hits' => $this->hits,
            'misses' => $this->misses,
            'hit_rate' => round($hitRate, 2),
            'memory_usage' => $this->estimateMemoryUsage(),
        ];
    }

    /**
     * Clear the cache.
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->accessOrder = [];
        $this->hits = 0;
        $this->misses = 0;
    }

    /**
     * Get cache efficiency ratio.
     */
    public function getEfficiency(): float
    {
        $total = $this->hits + $this->misses;
        return $total > 0 ? $this->hits / $total : 0.0;
    }

    /**
     * Estimate memory usage of the cache.
     */
    private function estimateMemoryUsage(): int
    {
        $baseMemory = 0;
        
        foreach ($this->cache as $key => $uri) {
            // Estimate memory for key (string) + URI object
            $baseMemory += strlen($key) + 500; // Rough estimate for URI object
        }
        
        return $baseMemory;
    }

    /**
     * Pre-warm cache with common URIs.
     */
    public function preWarm(array $commonUris): void
    {
        foreach ($commonUris as $uri) {
            $this->getOrCreate($uri);
        }
    }

    /**
     * Get most frequently accessed URIs.
     */
    public function getMostAccessed(int $limit = 10): array
    {
        // For this simple implementation, return most recently accessed
        return array_slice(array_keys($this->accessOrder), -$limit, $limit, true);
    }
}