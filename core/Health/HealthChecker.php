<?php

/**
 * Health Checker
 *
 * Provides comprehensive system health checks including database connectivity,
 * cache system status, disk space, memory usage, and service availability.
 *
 * @package  AliveChMS\Core\Health
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Cache.php';

class HealthChecker
{
    private array $checks = [];
    private array $results = [];

    public function __construct()
    {
        $this->registerDefaultChecks();
    }

    /**
     * Register default health checks
     */
    private function registerDefaultChecks(): void
    {
        $this->addCheck('database', [$this, 'checkDatabase']);
        $this->addCheck('cache', [$this, 'checkCache']);
        $this->addCheck('disk_space', [$this, 'checkDiskSpace']);
        $this->addCheck('memory', [$this, 'checkMemory']);
        $this->addCheck('php_version', [$this, 'checkPhpVersion']);
        $this->addCheck('extensions', [$this, 'checkRequiredExtensions']);
        $this->addCheck('permissions', [$this, 'checkFilePermissions']);
    }

    /**
     * Add a custom health check
     */
    public function addCheck(string $name, callable $callback): void
    {
        $this->checks[$name] = $callback;
    }

    /**
     * Run all health checks
     */
    public function runAll(): array
    {
        $this->results = [];
        $overallStatus = 'healthy';

        foreach ($this->checks as $name => $callback) {
            $startTime = microtime(true);
            
            try {
                $result = $callback();
                $result['duration'] = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($result['status'] !== 'healthy') {
                    $overallStatus = 'unhealthy';
                }
            } catch (Exception $e) {
                $result = [
                    'status' => 'unhealthy',
                    'message' => 'Check failed: ' . $e->getMessage(),
                    'duration' => round((microtime(true) - $startTime) * 1000, 2)
                ];
                $overallStatus = 'unhealthy';
            }

            $this->results[$name] = $result;
        }

        return [
            'status' => $overallStatus,
            'timestamp' => date('c'),
            'checks' => $this->results,
            'summary' => $this->generateSummary()
        ];
    }

    /**
     * Run a specific health check
     */
    public function runCheck(string $name): array
    {
        if (!isset($this->checks[$name])) {
            return [
                'status' => 'unhealthy',
                'message' => "Unknown health check: $name"
            ];
        }

        $startTime = microtime(true);
        
        try {
            $result = $this->checks[$name]();
            $result['duration'] = round((microtime(true) - $startTime) * 1000, 2);
            return $result;
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Check failed: ' . $e->getMessage(),
                'duration' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $db = Database::getInstance();
            $result = $db->query("SELECT 1 as test");
            
            if ($result && isset($result[0]['test']) && $result[0]['test'] == 1) {
                return [
                    'status' => 'healthy',
                    'message' => 'Database connection successful'
                ];
            }
            
            return [
                'status' => 'unhealthy',
                'message' => 'Database query failed'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . uniqid();
            
            // Test cache write
            if (!Cache::set($testKey, $testValue, 60)) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache write failed'
                ];
            }
            
            // Test cache read
            $retrieved = Cache::get($testKey);
            if ($retrieved !== $testValue) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read failed'
                ];
            }
            
            // Clean up
            Cache::delete($testKey);
            
            $stats = Cache::stats();
            
            return [
                'status' => 'healthy',
                'message' => 'Cache system operational',
                'details' => [
                    'total_entries' => $stats['total_entries'] ?? 0,
                    'total_size_mb' => $stats['total_size_mb'] ?? 0
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache system failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace(): array
    {
        $path = __DIR__ . '/../../';
        $freeBytes = disk_free_space($path);
        $totalBytes = disk_total_space($path);
        
        if ($freeBytes === false || $totalBytes === false) {
            return [
                'status' => 'unhealthy',
                'message' => 'Unable to check disk space'
            ];
        }
        
        $freePercent = ($freeBytes / $totalBytes) * 100;
        $freeMB = round($freeBytes / 1024 / 1024, 2);
        $totalMB = round($totalBytes / 1024 / 1024, 2);
        
        $status = $freePercent > 10 ? 'healthy' : 'unhealthy';
        $message = $freePercent > 10 
            ? 'Sufficient disk space available' 
            : 'Low disk space warning';
        
        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'free_mb' => $freeMB,
                'total_mb' => $totalMB,
                'free_percent' => round($freePercent, 2)
            ]
        ];
    }

    /**
     * Check memory usage
     */
    private function checkMemory(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $peakUsage = memory_get_peak_usage(true);
        
        $usagePercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;
        $usageMB = round($memoryUsage / 1024 / 1024, 2);
        $limitMB = round($memoryLimit / 1024 / 1024, 2);
        $peakMB = round($peakUsage / 1024 / 1024, 2);
        
        $status = $usagePercent < 80 ? 'healthy' : 'unhealthy';
        $message = $usagePercent < 80 
            ? 'Memory usage within normal limits' 
            : 'High memory usage detected';
        
        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'usage_mb' => $usageMB,
                'limit_mb' => $limitMB,
                'peak_mb' => $peakMB,
                'usage_percent' => round($usagePercent, 2)
            ]
        ];
    }

    /**
     * Check PHP version
     */
    private function checkPhpVersion(): array
    {
        $version = PHP_VERSION;
        $minVersion = '8.0.0';
        
        $status = version_compare($version, $minVersion, '>=') ? 'healthy' : 'unhealthy';
        $message = $status === 'healthy' 
            ? "PHP version $version is supported" 
            : "PHP version $version is below minimum required $minVersion";
        
        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'current_version' => $version,
                'minimum_version' => $minVersion
            ]
        ];
    }

    /**
     * Check required PHP extensions
     */
    private function checkRequiredExtensions(): array
    {
        $required = ['json', 'mbstring', 'pdo', 'pdo_mysql'];
        $missing = [];
        
        foreach ($required as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }
        
        $status = empty($missing) ? 'healthy' : 'unhealthy';
        $message = empty($missing) 
            ? 'All required extensions are loaded' 
            : 'Missing required extensions: ' . implode(', ', $missing);
        
        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'required' => $required,
                'missing' => $missing
            ]
        ];
    }

    /**
     * Check file permissions
     */
    private function checkFilePermissions(): array
    {
        $paths = [
            __DIR__ . '/../../cache' => 'Cache directory',
            __DIR__ . '/../../logs' => 'Logs directory'
        ];
        
        $issues = [];
        
        foreach ($paths as $path => $description) {
            if (!is_dir($path)) {
                $issues[] = "$description does not exist: $path";
                continue;
            }
            
            if (!is_writable($path)) {
                $issues[] = "$description is not writable: $path";
            }
        }
        
        $status = empty($issues) ? 'healthy' : 'unhealthy';
        $message = empty($issues) 
            ? 'All required directories have proper permissions' 
            : 'Permission issues detected';
        
        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'issues' => $issues
            ]
        ];
    }

    /**
     * Generate summary of health check results
     */
    private function generateSummary(): array
    {
        $total = count($this->results);
        $healthy = 0;
        $unhealthy = 0;
        
        foreach ($this->results as $result) {
            if ($result['status'] === 'healthy') {
                $healthy++;
            } else {
                $unhealthy++;
            }
        }
        
        return [
            'total_checks' => $total,
            'healthy_checks' => $healthy,
            'unhealthy_checks' => $unhealthy,
            'health_percentage' => $total > 0 ? round(($healthy / $total) * 100, 2) : 0
        ];
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
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
}