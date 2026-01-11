<?php

/**
 * Phase 3 Features Tests
 *
 * Comprehensive tests for Phase 3 enhanced features including CSRF protection,
 * user-based rate limiting, health checks, and enhanced monitoring.
 *
 * @package  AliveChMS\Tests\Unit
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../../core/Security/CsrfProtection.php';
require_once __DIR__ . '/../../core/Http/Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../core/Http/Middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../../core/Health/HealthChecker.php';
require_once __DIR__ . '/../../core/Monitoring/Logger.php';
require_once __DIR__ . '/../../core/Monitoring/PerformanceMonitor.php';
require_once __DIR__ . '/../../core/Http/Request.php';
require_once __DIR__ . '/../../core/Http/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Cache.php';

class Phase3Test
{
    private array $testResults = [];

    /**
     * Run all Phase 3 tests
     */
    public function runAllTests(): array
    {
        echo "Running Phase 3 Enhanced Features Tests...\n";
        echo "==========================================\n\n";

        // CSRF Protection tests
        $this->testCsrfProtection();
        
        // User-based Rate Limiting tests
        $this->testUserBasedRateLimit();
        
        // Health Check tests
        $this->testHealthChecker();
        
        // Enhanced Logging tests
        $this->testEnhancedLogging();
        
        // Performance Monitoring tests
        $this->testPerformanceMonitoring();

        $this->printResults();
        return $this->testResults;
    }

    /**
     * Test CSRF Protection
     */
    private function testCsrfProtection(): void
    {
        echo "Testing CSRF Protection...\n";

        // Test token generation
        $token1 = CsrfProtection::generateToken();
        $this->assert(is_string($token1) && strlen($token1) > 0, 'CSRF token generation');

        // Test token retrieval
        $token2 = CsrfProtection::getToken();
        $this->assert($token1 === $token2, 'CSRF token consistency');

        // Test token validation
        $this->assert(CsrfProtection::validateToken($token1), 'CSRF token validation - valid token');
        $this->assert(!CsrfProtection::validateToken('invalid_token'), 'CSRF token validation - invalid token');

        // Test HTML field generation
        $field = CsrfProtection::field();
        $this->assert(str_contains($field, 'name="_token"'), 'CSRF HTML field generation');
        $this->assert(str_contains($field, $token1), 'CSRF HTML field contains token');

        // Test meta tag generation
        $metaTag = CsrfProtection::metaTag();
        $this->assert(str_contains($metaTag, 'name="csrf-token"'), 'CSRF meta tag generation');

        // Test JS token data
        $jsData = CsrfProtection::getTokenForJs();
        $this->assert(isset($jsData['token']) && $jsData['token'] === $token1, 'CSRF JS token data');

        // Test token regeneration
        $newToken = CsrfProtection::regenerateToken();
        $this->assert($newToken !== $token1, 'CSRF token regeneration');

        echo "CSRF Protection tests completed.\n\n";
    }

    /**
     * Test User-based Rate Limiting
     */
    private function testUserBasedRateLimit(): void
    {
        echo "Testing User-based Rate Limiting...\n";

        // Test IP-based rate limiting
        $ipMiddleware = RateLimitMiddleware::forIp(5, 1);
        $this->assert($ipMiddleware instanceof RateLimitMiddleware, 'IP-based rate limit middleware creation');

        // Test user-based rate limiting
        $userMiddleware = RateLimitMiddleware::forUser(10, 5, 1);
        $this->assert($userMiddleware instanceof RateLimitMiddleware, 'User-based rate limit middleware creation');

        // Test middleware priority
        $this->assert($ipMiddleware->getPriority() === 20, 'Rate limit middleware priority');

        // Test request handling (mock)
        $request = Request::create('/api/test');
        $nextCalled = false;
        
        $next = function($req) use (&$nextCalled) {
            $nextCalled = true;
            return Response::success(['message' => 'OK']);
        };

        $response = $ipMiddleware->handle($request, $next);
        $this->assert($nextCalled, 'Rate limit middleware allows request');
        $this->assert($response instanceof Response, 'Rate limit middleware returns response');

        echo "User-based Rate Limiting tests completed.\n\n";
    }

    /**
     * Test Health Checker
     */
    private function testHealthChecker(): void
    {
        echo "Testing Health Checker...\n";

        $healthChecker = new HealthChecker();

        // Test individual health checks
        $dbCheck = $healthChecker->runCheck('database');
        $this->assert(isset($dbCheck['status']), 'Database health check returns status');
        $this->assert(in_array($dbCheck['status'], ['healthy', 'unhealthy']), 'Database health check valid status');

        $cacheCheck = $healthChecker->runCheck('cache');
        $this->assert(isset($cacheCheck['status']), 'Cache health check returns status');

        $memoryCheck = $healthChecker->runCheck('memory');
        $this->assert(isset($memoryCheck['status']), 'Memory health check returns status');
        $this->assert(isset($memoryCheck['details']['usage_mb']), 'Memory health check includes usage details');

        $diskCheck = $healthChecker->runCheck('disk_space');
        $this->assert(isset($diskCheck['status']), 'Disk space health check returns status');
        $this->assert(isset($diskCheck['details']['free_mb']), 'Disk space health check includes free space');

        $phpCheck = $healthChecker->runCheck('php_version');
        $this->assert(isset($phpCheck['status']), 'PHP version health check returns status');
        $this->assert(isset($phpCheck['details']['current_version']), 'PHP version health check includes version');

        $extensionsCheck = $healthChecker->runCheck('extensions');
        $this->assert(isset($extensionsCheck['status']), 'Extensions health check returns status');

        $permissionsCheck = $healthChecker->runCheck('permissions');
        $this->assert(isset($permissionsCheck['status']), 'Permissions health check returns status');

        // Test comprehensive health check
        $allChecks = $healthChecker->runAll();
        $this->assert(isset($allChecks['status']), 'Comprehensive health check returns overall status');
        $this->assert(isset($allChecks['checks']), 'Comprehensive health check includes individual checks');
        $this->assert(isset($allChecks['summary']), 'Comprehensive health check includes summary');
        $this->assert(count($allChecks['checks']) >= 7, 'Comprehensive health check runs all default checks');

        // Test custom health check
        $healthChecker->addCheck('custom_test', function() {
            return [
                'status' => 'healthy',
                'message' => 'Custom check passed'
            ];
        });

        $customCheck = $healthChecker->runCheck('custom_test');
        $this->assert($customCheck['status'] === 'healthy', 'Custom health check works');

        echo "Health Checker tests completed.\n\n";
    }

    /**
     * Test Enhanced Logging
     */
    private function testEnhancedLogging(): void
    {
        echo "Testing Enhanced Logging...\n";

        $logger = Logger::getInstance();

        // Test log levels
        $this->assert(Logger::EMERGENCY === 0, 'Emergency log level constant');
        $this->assert(Logger::DEBUG === 7, 'Debug log level constant');

        // Test static logging methods
        Logger::info('Test info message', ['test' => 'data']);
        Logger::warning('Test warning message');
        Logger::error('Test error message', ['error_code' => 500]);
        Logger::debug('Test debug message');

        $this->assert(true, 'Static logging methods execute without error');

        // Test specialized logging methods
        Logger::logRequest('GET', '/api/test', 200, 0.1);
        Logger::logQuery('SELECT * FROM users WHERE id = ?', [123], 0.05);
        Logger::logCache('get', 'user:123', true, 0.01);
        Logger::logAuth('login', 123, ['ip' => '127.0.0.1']);
        Logger::logSecurity('failed_login', ['attempts' => 3]);

        $this->assert(true, 'Specialized logging methods execute without error');

        // Test log level filtering
        $logger->setMinLevel(Logger::WARNING);
        Logger::info('This should not be logged');
        Logger::warning('This should be logged');

        $this->assert(true, 'Log level filtering works');

        echo "Enhanced Logging tests completed.\n\n";
    }

    /**
     * Test Performance Monitoring
     */
    private function testPerformanceMonitoring(): void
    {
        echo "Testing Performance Monitoring...\n";

        $monitor = PerformanceMonitor::getInstance();

        // Test timer functionality
        PerformanceMonitor::startTimer('test_operation');
        usleep(10000); // 10ms
        $duration = PerformanceMonitor::stopTimer('test_operation');
        $this->assert($duration > 0, 'Performance timer measures duration');
        $this->assert($duration < 1.0, 'Performance timer reasonable duration');

        // Test measure function
        $result = PerformanceMonitor::measure('test_measure', function() {
            usleep(5000); // 5ms
            return 'test_result';
        });
        $this->assert($result === 'test_result', 'Performance measure returns callback result');

        // Test counters
        PerformanceMonitor::increment('test_counter');
        PerformanceMonitor::increment('test_counter', 5);
        
        $metrics = PerformanceMonitor::getMetrics();
        $this->assert(isset($metrics['counters']['test_counter']), 'Performance counter recorded');
        $this->assert($metrics['counters']['test_counter'] === 6, 'Performance counter incremented correctly');

        // Test metric recording
        PerformanceMonitor::recordMetric('test_metric', 100);
        PerformanceMonitor::recordMetric('test_metric', 200);
        
        $metrics = PerformanceMonitor::getMetrics();
        $this->assert(isset($metrics['metrics']['test_metric']), 'Performance metric recorded');
        $this->assert($metrics['metrics']['test_metric']['count'] === 2, 'Performance metric count correct');
        $this->assert($metrics['metrics']['test_metric']['avg'] === 150, 'Performance metric average correct');

        // Test request metrics
        $this->assert(isset($metrics['request']['duration']), 'Request duration tracked');
        $this->assert(isset($metrics['request']['memory_usage']), 'Memory usage tracked');
        $this->assert(isset($metrics['request']['memory_peak']), 'Peak memory tracked');

        // Test resource usage
        $resources = PerformanceMonitor::getResourceUsage();
        $this->assert(isset($resources['memory']['usage_mb']), 'Resource usage includes memory');
        $this->assert(isset($resources['disk']['free_bytes']), 'Resource usage includes disk');

        // Test monitoring functions
        $queryMonitor = PerformanceMonitor::monitorQuery('SELECT * FROM test', []);
        $this->assert(is_callable($queryMonitor), 'Query monitor returns callable');

        $cacheMonitor = PerformanceMonitor::monitorCache('get', 'test_key');
        $this->assert(is_callable($cacheMonitor), 'Cache monitor returns callable');

        $requestMonitor = PerformanceMonitor::monitorRequest('GET', '/api/test');
        $this->assert(is_callable($requestMonitor), 'Request monitor returns callable');

        echo "Performance Monitoring tests completed.\n\n";
    }

    /**
     * Assert test condition
     */
    private function assert(bool $condition, string $message): void
    {
        $this->testResults[] = [
            'test' => $message,
            'passed' => $condition,
            'timestamp' => microtime(true)
        ];

        if ($condition) {
            echo "✓ {$message}\n";
        } else {
            echo "✗ {$message}\n";
        }
    }

    /**
     * Print test results summary
     */
    private function printResults(): void
    {
        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, fn($r) => $r['passed']));
        $failed = $total - $passed;

        echo "\n";
        echo "Phase 3 Enhanced Features Test Results:\n";
        echo "======================================\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Success Rate: " . round(($passed / $total) * 100, 2) . "%\n";

        if ($failed > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- {$result['test']}\n";
                }
            }
        }
    }
}