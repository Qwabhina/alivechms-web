<?php

/**
 * Performance Monitor
 *
 * Tracks application performance metrics including response times,
 * memory usage, database queries, and cache operations.
 *
 * @package  AliveChMS\Core\Monitoring
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Logger.php';

class PerformanceMonitor
{
    private static ?self $instance = null;
    private array $timers = [];
    private array $counters = [];
    private array $metrics = [];
    private float $requestStartTime;

    public function __construct()
    {
        $this->requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Start a timer
     */
    public static function startTimer(string $name): void
    {
        self::getInstance()->timers[$name] = microtime(true);
    }

    /**
     * Stop a timer and return duration
     */
    public static function stopTimer(string $name): float
    {
        $instance = self::getInstance();
        
        if (!isset($instance->timers[$name])) {
            return 0.0;
        }

        $duration = microtime(true) - $instance->timers[$name];
        unset($instance->timers[$name]);

        // Log slow operations
        if ($duration > 1.0) { // Log operations taking more than 1 second
            Logger::warning('Slow Operation Detected', [
                'operation' => $name,
                'duration_ms' => round($duration * 1000, 2)
            ]);
        }

        return $duration;
    }

    /**
     * Measure execution time of a callable
     */
    public static function measure(string $name, callable $callback)
    {
        self::startTimer($name);
        
        try {
            $result = $callback();
            return $result;
        } finally {
            $duration = self::stopTimer($name);
            self::recordMetric($name . '_duration', $duration);
        }
    }

    /**
     * Increment a counter
     */
    public static function increment(string $name, int $value = 1): void
    {
        $instance = self::getInstance();
        $instance->counters[$name] = ($instance->counters[$name] ?? 0) + $value;
    }

    /**
     * Record a metric value
     */
    public static function recordMetric(string $name, $value): void
    {
        $instance = self::getInstance();
        
        if (!isset($instance->metrics[$name])) {
            $instance->metrics[$name] = [];
        }
        
        $instance->metrics[$name][] = [
            'value' => $value,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Get current performance metrics
     */
    public static function getMetrics(): array
    {
        $instance = self::getInstance();
        
        return [
            'request' => [
                'duration' => microtime(true) - $instance->requestStartTime,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit')
            ],
            'counters' => $instance->counters,
            'metrics' => $instance->processMetrics(),
            'timers' => array_keys($instance->timers) // Active timers
        ];
    }

    /**
     * Process raw metrics into statistics
     */
    private function processMetrics(): array
    {
        $processed = [];
        
        foreach ($this->metrics as $name => $values) {
            if (empty($values)) {
                continue;
            }
            
            $numericValues = array_column($values, 'value');
            
            $processed[$name] = [
                'count' => count($numericValues),
                'min' => min($numericValues),
                'max' => max($numericValues),
                'avg' => array_sum($numericValues) / count($numericValues),
                'total' => array_sum($numericValues)
            ];
        }
        
        return $processed;
    }

    /**
     * Log performance summary
     */
    public static function logSummary(): void
    {
        $metrics = self::getMetrics();
        
        Logger::info('Request Performance Summary', [
            'duration_ms' => round($metrics['request']['duration'] * 1000, 2),
            'memory_usage_mb' => round($metrics['request']['memory_usage'] / 1024 / 1024, 2),
            'memory_peak_mb' => round($metrics['request']['memory_peak'] / 1024 / 1024, 2),
            'counters' => $metrics['counters'],
            'metrics' => $metrics['metrics']
        ]);
    }

    /**
     * Monitor database query
     */
    public static function monitorQuery(string $query, array $bindings = []): callable
    {
        return function() use ($query, $bindings) {
            $startTime = microtime(true);
            
            return function($result = null) use ($query, $bindings, $startTime) {
                $duration = microtime(true) - $startTime;
                
                self::increment('database_queries');
                self::recordMetric('query_duration', $duration);
                
                Logger::logQuery($query, $bindings, $duration);
                
                // Log slow queries
                if ($duration > 0.5) { // Log queries taking more than 500ms
                    Logger::warning('Slow Database Query', [
                        'query' => $query,
                        'bindings' => $bindings,
                        'duration_ms' => round($duration * 1000, 2)
                    ]);
                }
                
                return $result;
            };
        };
    }

    /**
     * Monitor cache operation
     */
    public static function monitorCache(string $operation, string $key): callable
    {
        return function($hit = null) use ($operation, $key) {
            $startTime = microtime(true);
            
            return function($result = null) use ($operation, $key, $hit, $startTime) {
                $duration = microtime(true) - $startTime;
                
                self::increment("cache_{$operation}");
                if ($hit !== null) {
                    self::increment($hit ? 'cache_hits' : 'cache_misses');
                }
                self::recordMetric('cache_duration', $duration);
                
                Logger::logCache($operation, $key, $hit, $duration);
                
                return $result;
            };
        };
    }

    /**
     * Monitor HTTP request
     */
    public static function monitorRequest(string $method, string $uri): callable
    {
        $startTime = microtime(true);
        
        return function(int $statusCode) use ($method, $uri, $startTime) {
            $duration = microtime(true) - $startTime;
            
            self::increment('http_requests');
            self::increment("http_status_{$statusCode}");
            self::recordMetric('request_duration', $duration);
            
            Logger::logRequest($method, $uri, $statusCode, $duration);
            
            // Log slow requests
            if ($duration > 2.0) { // Log requests taking more than 2 seconds
                Logger::warning('Slow HTTP Request', [
                    'method' => $method,
                    'uri' => $uri,
                    'status_code' => $statusCode,
                    'duration_ms' => round($duration * 1000, 2)
                ]);
            }
        };
    }

    /**
     * Get system resource usage
     */
    public static function getResourceUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = self::parseMemoryLimit(ini_get('memory_limit'));
        
        return [
            'memory' => [
                'usage_bytes' => $memoryUsage,
                'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'peak_bytes' => $memoryPeak,
                'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
                'limit_bytes' => $memoryLimit,
                'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                'usage_percent' => $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0
            ],
            'cpu' => [
                'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : null
            ],
            'disk' => [
                'free_bytes' => disk_free_space(__DIR__),
                'total_bytes' => disk_total_space(__DIR__)
            ]
        ];
    }

    /**
     * Parse memory limit string to bytes
     */
    private static function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0; // Unlimited
        }
        
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int)$limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Register shutdown function to log final metrics
     */
    public static function registerShutdownHandler(): void
    {
        register_shutdown_function([self::class, 'logSummary']);
    }
}