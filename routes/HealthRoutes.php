<?php

/**
 * Health Check Routes
 *
 * Provides health check endpoints for monitoring system status,
 * database connectivity, cache system, and overall application health.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Http/BaseHttpRoute.php';
require_once __DIR__ . '/../core/Health/HealthChecker.php';

class HealthRoutes extends BaseHttpRoute
{
    private HealthChecker $healthChecker;

    public function __construct()
    {
        parent::__construct();
        $this->healthChecker = new HealthChecker();
    }

    /**
     * GET /health - Basic health check
     */
    public function index(Request $request): Response
    {
        $results = $this->healthChecker->runAll();
        
        $statusCode = $results['status'] === 'healthy' ? 200 : 503;
        
        return Response::json($results, $statusCode);
    }

    /**
     * GET /health/quick - Quick health check (basic status only)
     */
    public function quick(Request $request): Response
    {
        try {
            // Quick checks only
            $db = Database::getInstance();
            $db->query("SELECT 1");
            
            return Response::json([
                'status' => 'healthy',
                'timestamp' => date('c'),
                'message' => 'System operational'
            ]);
        } catch (Exception $e) {
            return Response::json([
                'status' => 'unhealthy',
                'timestamp' => date('c'),
                'message' => 'System issues detected',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * GET /health/detailed - Detailed health check with all metrics
     */
    public function detailed(Request $request): Response
    {
        $results = $this->healthChecker->runAll();
        
        // Add additional system information
        $results['system'] = [
            'php_version' => PHP_VERSION,
            'server_time' => date('c'),
            'timezone' => date_default_timezone_get(),
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'server_info' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'os' => PHP_OS,
                'architecture' => php_uname('m')
            ]
        ];
        
        $statusCode = $results['status'] === 'healthy' ? 200 : 503;
        
        return Response::json($results, $statusCode);
    }

    /**
     * GET /health/database - Database-specific health check
     */
    public function database(Request $request): Response
    {
        $result = $this->healthChecker->runCheck('database');
        
        // Add database-specific metrics
        try {
            $db = Database::getInstance();
            
            // Get database version
            $version = $db->query("SELECT VERSION() as version");
            $result['details']['database_version'] = $version[0]['version'] ?? 'Unknown';
            
            // Get connection info
            $result['details']['connection_info'] = [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'database' => $_ENV['DB_NAME'] ?? 'unknown'
            ];
            
        } catch (Exception $e) {
            $result['details']['error'] = $e->getMessage();
        }
        
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;
        
        return Response::json($result, $statusCode);
    }

    /**
     * GET /health/cache - Cache-specific health check
     */
    public function cache(Request $request): Response
    {
        $result = $this->healthChecker->runCheck('cache');
        
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;
        
        return Response::json($result, $statusCode);
    }

    /**
     * GET /health/status - Simple status endpoint (for load balancers)
     */
    public function status(Request $request): Response
    {
        return Response::json([
            'status' => 'ok',
            'timestamp' => time()
        ]);
    }

    /**
     * GET /health/ready - Readiness probe (for Kubernetes)
     */
    public function ready(Request $request): Response
    {
        try {
            // Check critical services
            $db = Database::getInstance();
            $db->query("SELECT 1");
            
            // Check cache
            Cache::set('readiness_check', 'ok', 10);
            $cacheResult = Cache::get('readiness_check');
            
            if ($cacheResult !== 'ok') {
                throw new Exception('Cache not ready');
            }
            
            return Response::json([
                'status' => 'ready',
                'timestamp' => date('c')
            ]);
            
        } catch (Exception $e) {
            return Response::json([
                'status' => 'not_ready',
                'timestamp' => date('c'),
                'error' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * GET /health/live - Liveness probe (for Kubernetes)
     */
    public function live(Request $request): Response
    {
        // Basic liveness check - just return OK if PHP is running
        return Response::json([
            'status' => 'alive',
            'timestamp' => date('c'),
            'uptime' => $this->getUptime()
        ]);
    }

    /**
     * GET /health/metrics - Prometheus-style metrics
     */
    public function metrics(Request $request): Response
    {
        $results = $this->healthChecker->runAll();
        
        $metrics = [];
        
        // Convert health check results to metrics format
        foreach ($results['checks'] as $name => $check) {
            $status = $check['status'] === 'healthy' ? 1 : 0;
            $metrics[] = "health_check_status{check=\"$name\"} $status";
            
            if (isset($check['duration'])) {
                $metrics[] = "health_check_duration_ms{check=\"$name\"} {$check['duration']}";
            }
        }
        
        // Add system metrics
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $metrics[] = "php_memory_usage_bytes $memoryUsage";
        $metrics[] = "php_memory_peak_bytes $peakMemory";
        
        // Cache metrics
        $cacheStats = Cache::stats();
        if (isset($cacheStats['total_entries'])) {
            $metrics[] = "cache_entries_total {$cacheStats['total_entries']}";
        }
        if (isset($cacheStats['total_size'])) {
            $metrics[] = "cache_size_bytes {$cacheStats['total_size']}";
        }
        
        return new Response(
            implode("\n", $metrics) . "\n",
            200,
            ['Content-Type' => 'text/plain; charset=utf-8']
        );
    }

    /**
     * Get system uptime (approximate)
     */
    private function getUptime(): int
    {
        // This is a simple approximation - in production you might want to track actual start time
        $startTime = $_SERVER['REQUEST_TIME'] ?? time();
        return time() - $startTime;
    }
}