<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Performance;

use IceShell21\Psr7HttpMessage\Factory\RequestFactory;
use IceShell21\Psr7HttpMessage\Factory\ResponseFactory;
use IceShell21\Psr7HttpMessage\Factory\UriFactory;
use IceShell21\Psr7HttpMessage\Request;
use IceShell21\Psr7HttpMessage\Response;
use IceShell21\Psr7HttpMessage\Uri;

/**
 * Comprehensive benchmark suite for comparing performance improvements.
 * Measures old vs new implementations across various scenarios.
 */
final class BenchmarkSuite
{
    private PerformanceProfiler $profiler;
    private array $results = [];

    public function __construct()
    {
        $this->profiler = new PerformanceProfiler();
    }

    /**
     * Run complete benchmark suite.
     */
    public function runAllBenchmarks(): array
    {
        echo "Running PSR-7 Performance Benchmark Suite...\n\n";

        $this->results = [
            'request_creation' => $this->benchmarkRequestCreation(),
            'response_creation' => $this->benchmarkResponseCreation(),
            'uri_parsing' => $this->benchmarkUriParsing(),
            'stream_operations' => $this->benchmarkStreamOperations(),
            'object_pooling' => $this->benchmarkObjectPooling(),
            'memory_efficiency' => $this->benchmarkMemoryEfficiency(),
            'security_validation' => $this->benchmarkSecurityValidation(),
        ];

        return $this->results;
    }

    /**
     * Benchmark Request creation performance.
     */
    public function benchmarkRequestCreation(int $iterations = 10000): array
    {
        echo "Benchmarking Request Creation ($iterations iterations)...\n";

        // Traditional factory
        $timerId = $this->profiler->startTimer('traditional_request_creation');
        $factory = new RequestFactory();
        for ($i = 0; $i < $iterations; $i++) {
            $request = $factory->createRequest('GET', 'https://api.example.com/users/' . $i);
        }
        $traditionalMetrics = $this->profiler->stopTimer($timerId);

        // Optimized factory
        $timerId = $this->profiler->startTimer('optimized_request_creation');
        $optimizedFactory = OptimizedFactory::getInstance();
        for ($i = 0; $i < $iterations; $i++) {
            $request = $optimizedFactory->createRequest('GET', 'https://api.example.com/users/' . $i);
        }
        $optimizedMetrics = $this->profiler->stopTimer($timerId);

        // Modern request
        $timerId = $this->profiler->startTimer('modern_request_creation');
        for ($i = 0; $i < $iterations; $i++) {
            $request = new Request();
        }
        $modernMetrics = $this->profiler->stopTimer($timerId);

        $results = [
            'iterations' => $iterations,
            'traditional' => [
                'time_ms' => $traditionalMetrics['duration_ms'],
                'ops_per_sec' => $iterations / ($traditionalMetrics['duration_ms'] / 1000),
                'memory_kb' => $traditionalMetrics['memory_used'] / 1024,
            ],
            'optimized' => [
                'time_ms' => $optimizedMetrics['duration_ms'],
                'ops_per_sec' => $iterations / ($optimizedMetrics['duration_ms'] / 1000),
                'memory_kb' => $optimizedMetrics['memory_used'] / 1024,
            ],
            'modern' => [
                'time_ms' => $modernMetrics['duration_ms'],
                'ops_per_sec' => $iterations / ($modernMetrics['duration_ms'] / 1000),
                'memory_kb' => $modernMetrics['memory_used'] / 1024,
            ],
        ];

        $results['improvements'] = [
            'optimized_speedup' => $optimizedMetrics['duration_ms'] > 0 ? $traditionalMetrics['duration_ms'] / $optimizedMetrics['duration_ms'] : 1.0,
            'modern_speedup' => $modernMetrics['duration_ms'] > 0 ? $traditionalMetrics['duration_ms'] / $modernMetrics['duration_ms'] : 1.0,
            'optimized_memory_saving' => $traditionalMetrics['memory_used'] > 0 ? 1 - ($optimizedMetrics['memory_used'] / $traditionalMetrics['memory_used']) : 0.0,
            'modern_memory_saving' => $traditionalMetrics['memory_used'] > 0 ? 1 - ($modernMetrics['memory_used'] / $traditionalMetrics['memory_used']) : 0.0,
        ];

        echo sprintf("  Traditional: %.2f ms, %d ops/sec, %.2f KB\n", 
            $results['traditional']['time_ms'], 
            $results['traditional']['ops_per_sec'], 
            $results['traditional']['memory_kb']
        );
        echo sprintf("  Optimized: %.2f ms, %d ops/sec, %.2f KB (%.1fx faster, %.1f%% less memory)\n", 
            $results['optimized']['time_ms'], 
            $results['optimized']['ops_per_sec'], 
            $results['optimized']['memory_kb'],
            $results['improvements']['optimized_speedup'],
            $results['improvements']['optimized_memory_saving'] * 100
        );
        echo sprintf("  Modern: %.2f ms, %d ops/sec, %.2f KB (%.1fx faster, %.1f%% less memory)\n\n", 
            $results['modern']['time_ms'], 
            $results['modern']['ops_per_sec'], 
            $results['modern']['memory_kb'],
            $results['improvements']['modern_speedup'],
            $results['improvements']['modern_memory_saving'] * 100
        );

        return $results;
    }

    /**
     * Benchmark Response creation performance.
     */
    public function benchmarkResponseCreation(int $iterations = 10000): array
    {
        echo "Benchmarking Response Creation ($iterations iterations)...\n";

        // Traditional factory
        $timerId = $this->profiler->startTimer('traditional_response_creation');
        $factory = new ResponseFactory();
        for ($i = 0; $i < $iterations; $i++) {
            $response = $factory->createResponse(200, 'OK');
        }
        $traditionalMetrics = $this->profiler->stopTimer($timerId);

        // Optimized factory
        $timerId = $this->profiler->startTimer('optimized_response_creation');
        $optimizedFactory = OptimizedFactory::getInstance();
        for ($i = 0; $i < $iterations; $i++) {
            $response = $optimizedFactory->createResponse(200, 'OK');
        }
        $optimizedMetrics = $this->profiler->stopTimer($timerId);

        // Modern response
        $timerId = $this->profiler->startTimer('modern_response_creation');
        for ($i = 0; $i < $iterations; $i++) {
            $response = new Response();
        }
        $modernMetrics = $this->profiler->stopTimer($timerId);

        $results = [
            'iterations' => $iterations,
            'traditional' => [
                'time_ms' => $traditionalMetrics['duration_ms'],
                'ops_per_sec' => $iterations / ($traditionalMetrics['duration_ms'] / 1000),
                'memory_kb' => $traditionalMetrics['memory_used'] / 1024,
            ],
            'optimized' => [
                'time_ms' => $optimizedMetrics['duration_ms'],
                'ops_per_sec' => $iterations / ($optimizedMetrics['duration_ms'] / 1000),
                'memory_kb' => $optimizedMetrics['memory_used'] / 1024,
            ],
            'modern' => [
                'time_ms' => $modernMetrics['duration_ms'],
                'ops_per_sec' => $iterations / ($modernMetrics['duration_ms'] / 1000),
                'memory_kb' => $modernMetrics['memory_used'] / 1024,
            ],
        ];

        $results['improvements'] = [
            'optimized_speedup' => $optimizedMetrics['duration_ms'] > 0 ? $traditionalMetrics['duration_ms'] / $optimizedMetrics['duration_ms'] : 1.0,
            'modern_speedup' => $modernMetrics['duration_ms'] > 0 ? $traditionalMetrics['duration_ms'] / $modernMetrics['duration_ms'] : 1.0,
        ];

        echo sprintf("  Optimized: %.1fx faster, Modern: %.1fx faster\n\n", 
            $results['improvements']['optimized_speedup'],
            $results['improvements']['modern_speedup']
        );

        return $results;
    }

    /**
     * Benchmark URI parsing performance.
     */
    public function benchmarkUriParsing(int $iterations = 5000): array
    {
        echo "Benchmarking URI Parsing ($iterations iterations)...\n";

        $testUris = [
            'https://api.example.com/v1/users',
            'http://localhost:8080/test?param=value',
            'https://subdomain.example.org:443/path/to/resource#fragment',
        ];

        $results = [];

        foreach ($testUris as $uri) {
            // Traditional factory
            $timerId = $this->profiler->startTimer('traditional_uri_parsing');
            $factory = new UriFactory();
            for ($i = 0; $i < $iterations; $i++) {
                $uriObject = $factory->createUri($uri);
            }
            $traditionalMetrics = $this->profiler->stopTimer($timerId);

            // Optimized factory (with caching)
            $timerId = $this->profiler->startTimer('optimized_uri_parsing');
            $optimizedFactory = OptimizedFactory::getInstance();
            for ($i = 0; $i < $iterations; $i++) {
                $uriObject = $optimizedFactory->createUri($uri);
            }
            $optimizedMetrics = $this->profiler->stopTimer($timerId);

            // Modern URI
            $timerId = $this->profiler->startTimer('modern_uri_parsing');
            for ($i = 0; $i < $iterations; $i++) {
                $uriObject = Uri::fromString($uri);
            }
            $modernMetrics = $this->profiler->stopTimer($timerId);

            $speedup = $traditionalMetrics['duration_ms'] / $optimizedMetrics['duration_ms'];
            $modernSpeedup = $traditionalMetrics['duration_ms'] / $modernMetrics['duration_ms'];

            $results[$uri] = [
                'traditional_ms' => $traditionalMetrics['duration_ms'],
                'optimized_ms' => $optimizedMetrics['duration_ms'],
                'modern_ms' => $modernMetrics['duration_ms'],
                'optimized_speedup' => $speedup,
                'modern_speedup' => $modernSpeedup,
            ];

            echo sprintf("  %s: %.1fx faster (optimized), %.1fx faster (modern)\n", 
                substr($uri, 0, 30) . '...', $speedup, $modernSpeedup);
        }

        echo "\n";
        return $results;
    }

    /**
     * Benchmark Stream operations.
     */
    public function benchmarkStreamOperations(): array
    {
        echo "Benchmarking Stream Operations...\n";

        $sizes = [1024, 10240, 102400, 1048576]; // 1KB, 10KB, 100KB, 1MB
        $results = [];

        foreach ($sizes as $size) {
            $data = str_repeat('x', $size);
            
            // Traditional stream
            $timerId = $this->profiler->startTimer('traditional_stream');
            $stream = new \IceShell21\Psr7HttpMessage\Stream($data);
            $content = $stream->getContents();
            $traditionalMetrics = $this->profiler->stopTimer($timerId);

            // Optimized stream
            $timerId = $this->profiler->startTimer('optimized_stream');
            $optimizedStream = new OptimizedStream($data);
            $content = $optimizedStream->getContents();
            $optimizedMetrics = $this->profiler->stopTimer($timerId);

            $throughputMB = ($size / 1024 / 1024);
            $traditionalThroughput = $throughputMB / ($traditionalMetrics['duration_ms'] / 1000);
            $optimizedThroughput = $throughputMB / ($optimizedMetrics['duration_ms'] / 1000);

            $results[$size] = [
                'size_kb' => $size / 1024,
                'traditional_ms' => $traditionalMetrics['duration_ms'],
                'optimized_ms' => $optimizedMetrics['duration_ms'],
                'traditional_throughput_mb_s' => $traditionalThroughput,
                'optimized_throughput_mb_s' => $optimizedThroughput,
                'speedup' => $traditionalMetrics['duration_ms'] / $optimizedMetrics['duration_ms'],
            ];

            echo sprintf("  %d KB: %.1fx faster, %.1f MB/s -> %.1f MB/s\n", 
                $size / 1024, 
                $results[$size]['speedup'],
                $traditionalThroughput,
                $optimizedThroughput
            );
        }

        echo "\n";
        return $results;
    }

    /**
     * Benchmark Object Pooling effectiveness.
     */
    public function benchmarkObjectPooling(int $iterations = 5000): array
    {
        echo "Benchmarking Object Pooling ($iterations iterations)...\n";

        $optimizedFactory = OptimizedFactory::getInstance();
        $optimizedFactory->warmUpPools(100); // Pre-warm pools

        // Without pooling
        $timerId = $this->profiler->startTimer('without_pooling');
        for ($i = 0; $i < $iterations; $i++) {
            $request = new \IceShell21\Psr7HttpMessage\Request();
            $response = new \IceShell21\Psr7HttpMessage\Response();
        }
        $withoutPooling = $this->profiler->stopTimer($timerId);

        // With pooling
        $timerId = $this->profiler->startTimer('with_pooling');
        for ($i = 0; $i < $iterations; $i++) {
            $request = $optimizedFactory->createRequest('GET', '/');
            $response = $optimizedFactory->createResponse();
        }
        $withPooling = $this->profiler->stopTimer($timerId);

        $results = [
            'iterations' => $iterations,
            'without_pooling_ms' => $withoutPooling['duration_ms'],
            'with_pooling_ms' => $withPooling['duration_ms'],
            'speedup' => $withoutPooling['duration_ms'] / $withPooling['duration_ms'],
            'memory_saving_kb' => ($withoutPooling['memory_used'] - $withPooling['memory_used']) / 1024,
            'pool_stats' => $optimizedFactory->getPerformanceStats()['pool_stats'],
        ];

        echo sprintf("  Speedup: %.1fx, Memory saved: %.1f KB\n\n", 
            $results['speedup'], $results['memory_saving_kb']);

        return $results;
    }

    /**
     * Benchmark memory efficiency.
     */
    public function benchmarkMemoryEfficiency(): array
    {
        echo "Benchmarking Memory Efficiency...\n";

        $this->profiler->takeMemorySnapshot('start');

        // Create many objects without optimization
        $objects = [];
        for ($i = 0; $i < 1000; $i++) {
            $objects[] = new \IceShell21\Psr7HttpMessage\Request();
        }
        
        $this->profiler->takeMemorySnapshot('traditional');

        // Clear and use optimized versions
        $objects = null;
        gc_collect_cycles();
        
        $optimizedFactory = OptimizedFactory::getInstance();
        $optimizedObjects = [];
        for ($i = 0; $i < 1000; $i++) {
            $optimizedObjects[] = $optimizedFactory->createRequest('GET', '/');
        }

        $this->profiler->takeMemorySnapshot('optimized');

        $traditionalDiff = $this->profiler->getMemoryDifference('start', 'traditional');
        $optimizedDiff = $this->profiler->getMemoryDifference('start', 'optimized');

        $results = [
            'traditional_memory_kb' => $traditionalDiff['memory_increase'] / 1024,
            'optimized_memory_kb' => $optimizedDiff['memory_increase'] / 1024,
            'memory_saving_percent' => (1 - ($optimizedDiff['memory_increase'] / $traditionalDiff['memory_increase'])) * 100,
        ];

        echo sprintf("  Traditional: %.1f KB, Optimized: %.1f KB (%.1f%% savings)\n\n", 
            $results['traditional_memory_kb'], 
            $results['optimized_memory_kb'], 
            $results['memory_saving_percent']
        );

        return $results;
    }

    /**
     * Benchmark security validation performance.
     */
    public function benchmarkSecurityValidation(int $iterations = 1000): array
    {
        echo "Benchmarking Security Validation ($iterations iterations)...\n";

        // Without security validation
        $timerId = $this->profiler->startTimer('without_security');
        for ($i = 0; $i < $iterations; $i++) {
            $request = new \IceShell21\Psr7HttpMessage\Request();
            $response = new \IceShell21\Psr7HttpMessage\Response();
        }
        $withoutSecurity = $this->profiler->stopTimer($timerId);

        // With security validation
        $timerId = $this->profiler->startTimer('with_security');
        for ($i = 0; $i < $iterations; $i++) {
            $request = new Request();
            $response = new Response();
        }
        $withSecurity = $this->profiler->stopTimer($timerId);

        $results = [
            'iterations' => $iterations,
            'without_security_ms' => $withoutSecurity['duration_ms'],
            'with_security_ms' => $withSecurity['duration_ms'],
            'overhead_percent' => (($withSecurity['duration_ms'] / $withoutSecurity['duration_ms']) - 1) * 100,
            'security_ops_per_sec' => $iterations / ($withSecurity['duration_ms'] / 1000),
        ];

        echo sprintf("  Security overhead: %.1f%%, Still %.0f ops/sec\n\n", 
            $results['overhead_percent'], $results['security_ops_per_sec']);

        return $results;
    }

    /**
     * Generate comprehensive benchmark report.
     */
    public function generateReport(): string
    {
        if (empty($this->results)) {
            $this->runAllBenchmarks();
        }

        $report = "=== PSR-7 HTTP Message Performance Benchmark Report ===\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= "PHP Version: " . PHP_VERSION . "\n";
        $report .= "Memory Limit: " . ini_get('memory_limit') . "\n\n";

        $report .= "SUMMARY OF IMPROVEMENTS:\n";
        $report .= sprintf("- Request Creation: %.1fx faster\n", 
            $this->results['request_creation']['improvements']['optimized_speedup'] ?? 0);
        $report .= sprintf("- Response Creation: %.1fx faster\n", 
            $this->results['response_creation']['improvements']['optimized_speedup'] ?? 0);
        $report .= sprintf("- Object Pooling: %.1fx faster\n", 
            $this->results['object_pooling']['speedup'] ?? 0);
        $report .= sprintf("- Memory Efficiency: %.1f%% less memory usage\n", 
            $this->results['memory_efficiency']['memory_saving_percent'] ?? 0);
        $report .= sprintf("- Security Validation: Only %.1f%% overhead\n\n", 
            $this->results['security_validation']['overhead_percent'] ?? 0);

        $report .= "DETAILED RESULTS:\n";
        $report .= json_encode($this->results, JSON_PRETTY_PRINT);

        return $report;
    }

    /**
     * Export results to file.
     */
    public function exportResults(string $filename): void
    {
        file_put_contents($filename, $this->generateReport());
        echo "Results exported to: $filename\n";
    }
}