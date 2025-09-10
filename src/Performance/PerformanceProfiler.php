<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Performance;

/**
 * High-precision performance profiler for HTTP message operations.
 * Tracks creation time, memory usage, and operation efficiency.
 */
final class PerformanceProfiler
{
    private array $metrics = [];
    private array $timers = [];
    private array $memorySnapshots = [];
    private int $operationCounter = 0;

    public function __construct(
        private readonly bool $enabled = true,
        private readonly int $maxMetrics = 10000
    ) {}

    /**
     * Start timing an operation.
     */
    public function startTimer(string $operation): string
    {
        if (!$this->enabled) {
            return '';
        }

        $timerId = $operation . '_' . ++$this->operationCounter;
        
        $this->timers[$timerId] = [
            'operation' => $operation,
            'start_time' => hrtime(true),
            'start_memory' => memory_get_usage(true),
            'start_peak_memory' => memory_get_peak_usage(true),
        ];

        return $timerId;
    }

    /**
     * Stop timing an operation and record metrics.
     */
    public function stopTimer(string $timerId): array
    {
        if (!$this->enabled || !isset($this->timers[$timerId])) {
            return [];
        }

        $timer = $this->timers[$timerId];
        $endTime = hrtime(true);
        $endMemory = memory_get_usage(true);
        $endPeakMemory = memory_get_peak_usage(true);

        $metrics = [
            'operation' => $timer['operation'],
            'duration_ns' => $endTime - $timer['start_time'],
            'duration_ms' => ($endTime - $timer['start_time']) / 1_000_000,
            'duration_us' => ($endTime - $timer['start_time']) / 1_000,
            'memory_used' => $endMemory - $timer['start_memory'],
            'peak_memory_increase' => $endPeakMemory - $timer['start_peak_memory'],
            'timestamp' => time(),
        ];

        $this->recordMetrics($metrics);
        unset($this->timers[$timerId]);

        return $metrics;
    }

    /**
     * Profile Request creation performance.
     */
    public function profileRequestCreation(int $iterations = 1000): array
    {
        if (!$this->enabled) {
            return [];
        }

        $timerId = $this->startTimer('request_creation_batch');
        
        for ($i = 0; $i < $iterations; $i++) {
            $singleTimer = $this->startTimer('request_creation');
            $factory = OptimizedFactory::getInstance();
            $request = $factory->createRequest('GET', 'https://example.com/api/test');
            $this->stopTimer($singleTimer);
        }

        $batchMetrics = $this->stopTimer($timerId);
        
        return [
            'iterations' => $iterations,
            'total_time_ms' => $batchMetrics['duration_ms'],
            'avg_time_us' => $batchMetrics['duration_us'] / $iterations,
            'ops_per_second' => $iterations / ($batchMetrics['duration_ms'] / 1000),
            'total_memory_kb' => $batchMetrics['memory_used'] / 1024,
            'avg_memory_bytes' => $batchMetrics['memory_used'] / $iterations,
        ];
    }

    /**
     * Profile Response creation performance.
     */
    public function profileResponseCreation(int $iterations = 1000): array
    {
        if (!$this->enabled) {
            return [];
        }

        $timerId = $this->startTimer('response_creation_batch');
        
        for ($i = 0; $i < $iterations; $i++) {
            $singleTimer = $this->startTimer('response_creation');
            $factory = OptimizedFactory::getInstance();
            $response = $factory->createResponse(200, 'OK');
            $this->stopTimer($singleTimer);
        }

        $batchMetrics = $this->stopTimer($timerId);
        
        return [
            'iterations' => $iterations,
            'total_time_ms' => $batchMetrics['duration_ms'],
            'avg_time_us' => $batchMetrics['duration_us'] / $iterations,
            'ops_per_second' => $iterations / ($batchMetrics['duration_ms'] / 1000),
            'total_memory_kb' => $batchMetrics['memory_used'] / 1024,
            'avg_memory_bytes' => $batchMetrics['memory_used'] / $iterations,
        ];
    }

    /**
     * Profile URI parsing performance.
     */
    public function profileUriParsing(array $uris, int $iterations = 100): array
    {
        if (!$this->enabled) {
            return [];
        }

        $results = [];
        
        foreach ($uris as $uri) {
            $timerId = $this->startTimer('uri_parsing_batch');
            
            for ($i = 0; $i < $iterations; $i++) {
                $singleTimer = $this->startTimer('uri_parsing');
                $factory = OptimizedFactory::getInstance();
                $uriObject = $factory->createUri($uri);
                $this->stopTimer($singleTimer);
            }
            
            $batchMetrics = $this->stopTimer($timerId);
            
            $results[$uri] = [
                'iterations' => $iterations,
                'total_time_ms' => $batchMetrics['duration_ms'],
                'avg_time_us' => $batchMetrics['duration_us'] / $iterations,
                'ops_per_second' => $iterations / ($batchMetrics['duration_ms'] / 1000),
            ];
        }

        return $results;
    }

    /**
     * Profile Stream operations.
     */
    public function profileStreamOperations(array $sizes = [1024, 10240, 102400]): array
    {
        if (!$this->enabled) {
            return [];
        }

        $results = [];
        
        foreach ($sizes as $size) {
            $data = str_repeat('x', $size);
            
            // Test stream creation
            $timerId = $this->startTimer('stream_creation');
            $stream = new OptimizedStream($data);
            $creationMetrics = $this->stopTimer($timerId);
            
            // Test stream reading
            $timerId = $this->startTimer('stream_reading');
            $content = $stream->getContents();
            $readingMetrics = $this->stopTimer($timerId);
            
            // Test stream writing
            $timerId = $this->startTimer('stream_writing');
            $writeStream = new OptimizedStream();
            $writeStream->write($data);
            $writingMetrics = $this->stopTimer($timerId);
            
            $results[$size] = [
                'size_bytes' => $size,
                'creation' => $creationMetrics,
                'reading' => $readingMetrics,
                'writing' => $writingMetrics,
                'throughput_mb_per_sec' => [
                    'read' => ($size / 1024 / 1024) / ($readingMetrics['duration_ms'] / 1000),
                    'write' => ($size / 1024 / 1024) / ($writingMetrics['duration_ms'] / 1000),
                ],
            ];
        }

        return $results;
    }

    /**
     * Get comprehensive performance statistics.
     */
    public function getStats(): array
    {
        if (!$this->enabled) {
            return ['enabled' => false];
        }

        $operationStats = [];
        
        foreach ($this->metrics as $metric) {
            $operation = $metric['operation'];
            
            if (!isset($operationStats[$operation])) {
                $operationStats[$operation] = [
                    'count' => 0,
                    'total_time_ms' => 0,
                    'total_memory' => 0,
                    'min_time_ms' => PHP_FLOAT_MAX,
                    'max_time_ms' => 0,
                    'times' => [],
                ];
            }
            
            $stats = &$operationStats[$operation];
            $stats['count']++;
            $stats['total_time_ms'] += $metric['duration_ms'];
            $stats['total_memory'] += $metric['memory_used'];
            $stats['min_time_ms'] = min($stats['min_time_ms'], $metric['duration_ms']);
            $stats['max_time_ms'] = max($stats['max_time_ms'], $metric['duration_ms']);
            $stats['times'][] = $metric['duration_ms'];
        }

        // Calculate averages and percentiles
        foreach ($operationStats as $operation => &$stats) {
            $stats['avg_time_ms'] = $stats['total_time_ms'] / $stats['count'];
            $stats['avg_memory_bytes'] = $stats['total_memory'] / $stats['count'];
            $stats['ops_per_second'] = $stats['count'] / ($stats['total_time_ms'] / 1000);
            
            sort($stats['times']);
            $count = count($stats['times']);
            $stats['percentiles'] = [
                'p50' => $stats['times'][intval($count * 0.5)],
                'p90' => $stats['times'][intval($count * 0.9)],
                'p95' => $stats['times'][intval($count * 0.95)],
                'p99' => $stats['times'][intval($count * 0.99)],
            ];
            
            unset($stats['times']); // Remove raw data to save memory
        }

        return [
            'enabled' => true,
            'total_operations' => count($this->metrics),
            'active_timers' => count($this->timers),
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
            ],
            'operations' => $operationStats,
        ];
    }

    /**
     * Record system resource usage snapshot.
     */
    public function takeMemorySnapshot(string $label): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->memorySnapshots[$label] = [
            'timestamp' => hrtime(true),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'allocated_memory' => memory_get_usage(false),
        ];
    }

    /**
     * Get memory usage between snapshots.
     */
    public function getMemoryDifference(string $startLabel, string $endLabel): array
    {
        if (!isset($this->memorySnapshots[$startLabel], $this->memorySnapshots[$endLabel])) {
            return [];
        }

        $start = $this->memorySnapshots[$startLabel];
        $end = $this->memorySnapshots[$endLabel];

        return [
            'time_difference_ms' => ($end['timestamp'] - $start['timestamp']) / 1_000_000,
            'memory_increase' => $end['memory_usage'] - $start['memory_usage'],
            'peak_memory_increase' => $end['peak_memory'] - $start['peak_memory'],
            'allocated_memory_increase' => $end['allocated_memory'] - $start['allocated_memory'],
        ];
    }

    /**
     * Generate performance report.
     */
    public function generateReport(): string
    {
        $stats = $this->getStats();
        
        if (!$stats['enabled']) {
            return "Performance profiling is disabled.\n";
        }

        $report = "=== Performance Report ===\n";
        $report .= "Total Operations: {$stats['total_operations']}\n";
        $report .= "Current Memory: " . number_format($stats['memory_usage']['current'] / 1024 / 1024, 2) . " MB\n";
        $report .= "Peak Memory: " . number_format($stats['memory_usage']['peak'] / 1024 / 1024, 2) . " MB\n\n";

        foreach ($stats['operations'] as $operation => $opStats) {
            $report .= "Operation: $operation\n";
            $report .= "  Count: {$opStats['count']}\n";
            $report .= "  Avg Time: " . number_format($opStats['avg_time_ms'], 3) . " ms\n";
            $report .= "  Min Time: " . number_format($opStats['min_time_ms'], 3) . " ms\n";
            $report .= "  Max Time: " . number_format($opStats['max_time_ms'], 3) . " ms\n";
            $report .= "  Ops/sec: " . number_format($opStats['ops_per_second'], 0) . "\n";
            $report .= "  Avg Memory: " . number_format($opStats['avg_memory_bytes'] / 1024, 2) . " KB\n";
            $report .= "  Percentiles: P50=" . number_format($opStats['percentiles']['p50'], 3) . 
                      "ms, P90=" . number_format($opStats['percentiles']['p90'], 3) . 
                      "ms, P95=" . number_format($opStats['percentiles']['p95'], 3) . 
                      "ms, P99=" . number_format($opStats['percentiles']['p99'], 3) . "ms\n\n";
        }

        return $report;
    }

    /**
     * Record metrics if under limit.
     */
    private function recordMetrics(array $metrics): void
    {
        if (count($this->metrics) >= $this->maxMetrics) {
            // Remove oldest metric to prevent memory issues
            array_shift($this->metrics);
        }
        
        $this->metrics[] = $metrics;
    }

    /**
     * Clear all recorded metrics and timers.
     */
    public function reset(): void
    {
        $this->metrics = [];
        $this->timers = [];
        $this->memorySnapshots = [];
        $this->operationCounter = 0;
    }

    /**
     * Export metrics as JSON.
     */
    public function exportMetrics(): string
    {
        return json_encode([
            'stats' => $this->getStats(),
            'snapshots' => $this->memorySnapshots,
        ], JSON_PRETTY_PRINT);
    }
}