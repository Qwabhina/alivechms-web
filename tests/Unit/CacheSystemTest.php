<?php

/**
 * Cache System Tests
 *
 * Comprehensive tests for the cache system including drivers,
 * manager, and service provider functionality.
 *
 * @package  AliveChMS\Tests\Unit
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../../core/Cache/CacheManager.php';
require_once __DIR__ . '/../../core/Cache/FileDriver.php';
require_once __DIR__ . '/../../core/Cache/MemoryDriver.php';
require_once __DIR__ . '/../../core/Providers/CacheServiceProvider.php';
require_once __DIR__ . '/../../core/Container.php';
require_once __DIR__ . '/../../core/Cache.php';

class CacheSystemTest
{
    private array $testResults = [];
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = sys_get_temp_dir() . '/alivechms_cache_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/tags', 0755, true);
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    /**
     * Run all cache system tests
     */
    public function runAllTests(): array
    {
        echo "Running Cache System Tests...\n";
        echo "=============================\n\n";

        // Driver tests
        $this->testFileDriver();
        $this->testMemoryDriver();
        
        // Manager tests
        $this->testCacheManager();
        $this->testCacheManagerFallback();
        $this->testCacheManagerReplication();
        
        // Service provider tests
        $this->testCacheServiceProvider();
        
        // Integration tests
        $this->testCacheIntegration();
        $this->testCachePerformance();

        $this->printResults();
        return $this->testResults;
    }

    /**
     * Test File Driver
     */
    private function testFileDriver(): void
    {
        echo "Testing File Driver...\n";

        $config = [
            'cache_dir' => $this->tempDir,
            'tag_dir' => $this->tempDir . '/tags'
        ];
        
        $driver = new FileDriver($config);

        // Basic operations
        $this->assert($driver->set('test_key', 'test_value'), 'File driver set operation');
        $this->assert($driver->get('test_key') === 'test_value', 'File driver get operation');
        $this->assert($driver->has('test_key'), 'File driver has operation');
        $this->assert($driver->delete('test_key'), 'File driver delete operation');
        $this->assert($driver->get('test_key') === null, 'File driver get after delete');

        // TTL operations
        $this->assert($driver->set('ttl_key', 'ttl_value', 1), 'File driver TTL set');
        $this->assert($driver->get('ttl_key') === 'ttl_value', 'File driver TTL get immediate');
        sleep(2);
        $this->assert($driver->get('ttl_key') === null, 'File driver TTL expiration');

        // Multiple operations
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assert($driver->setMultiple($data), 'File driver setMultiple');
        $result = $driver->getMultiple(['key1', 'key2']);
        $this->assert($result['key1'] === 'value1' && $result['key2'] === 'value2', 'File driver getMultiple');

        // Tag operations
        $this->assert($driver->set('tagged_key', 'tagged_value', 3600, ['tag1', 'tag2']), 'File driver tagged set');
        $this->assert($driver->invalidateTag('tag1') > 0, 'File driver tag invalidation');
        $this->assert($driver->get('tagged_key') === null, 'File driver get after tag invalidation');

        // Statistics
        $stats = $driver->getStats();
        $this->assert(isset($stats['driver']) && $stats['driver'] === 'file', 'File driver statistics');

        echo "File Driver tests completed.\n\n";
    }

    /**
     * Test Memory Driver
     */
    private function testMemoryDriver(): void
    {
        echo "Testing Memory Driver...\n";

        $config = ['max_memory' => 1024 * 1024]; // 1MB
        $driver = new MemoryDriver($config);

        // Basic operations
        $this->assert($driver->set('mem_key', 'mem_value'), 'Memory driver set operation');
        $this->assert($driver->get('mem_key') === 'mem_value', 'Memory driver get operation');
        $this->assert($driver->has('mem_key'), 'Memory driver has operation');
        $this->assert($driver->delete('mem_key'), 'Memory driver delete operation');
        $this->assert($driver->get('mem_key') === null, 'Memory driver get after delete');

        // TTL operations
        $this->assert($driver->set('ttl_mem_key', 'ttl_mem_value', 1), 'Memory driver TTL set');
        $this->assert($driver->get('ttl_mem_key') === 'ttl_mem_value', 'Memory driver TTL get immediate');
        sleep(2);
        $this->assert($driver->get('ttl_mem_key') === null, 'Memory driver TTL expiration');

        // Multiple operations
        $data = ['mem_key1' => 'mem_value1', 'mem_key2' => 'mem_value2'];
        $this->assert($driver->setMultiple($data), 'Memory driver setMultiple');
        $result = $driver->getMultiple(['mem_key1', 'mem_key2']);
        $this->assert($result['mem_key1'] === 'mem_value1' && $result['mem_key2'] === 'mem_value2', 'Memory driver getMultiple');

        // Tag operations
        $this->assert($driver->set('tagged_mem_key', 'tagged_mem_value', 3600, ['mem_tag1']), 'Memory driver tagged set');
        $this->assert($driver->invalidateTag('mem_tag1') > 0, 'Memory driver tag invalidation');
        $this->assert($driver->get('tagged_mem_key') === null, 'Memory driver get after tag invalidation');

        // Eviction test
        $largeData = str_repeat('x', 512 * 1024); // 512KB
        $this->assert($driver->set('large1', $largeData), 'Memory driver large data 1');
        $this->assert($driver->set('large2', $largeData), 'Memory driver large data 2');
        $this->assert($driver->set('large3', $largeData), 'Memory driver large data 3 (should trigger eviction)');

        // Statistics
        $stats = $driver->getStats();
        $this->assert(isset($stats['driver']) && $stats['driver'] === 'memory', 'Memory driver statistics');
        $this->assert(isset($stats['memory_used_mb']), 'Memory driver memory usage stats');

        echo "Memory Driver tests completed.\n\n";
    }

    /**
     * Test Cache Manager
     */
    private function testCacheManager(): void
    {
        echo "Testing Cache Manager...\n";

        $config = [
            'default' => 'file',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'cache_dir' => $this->tempDir,
                    'tag_dir' => $this->tempDir . '/tags'
                ],
                'memory' => [
                    'driver' => 'memory',
                    'max_memory' => 1024 * 1024
                ]
            ]
        ];

        $manager = new CacheManager($config);

        // Basic operations
        $this->assert($manager->set('manager_key', 'manager_value'), 'Cache manager set operation');
        $this->assert($manager->get('manager_key') === 'manager_value', 'Cache manager get operation');
        $this->assert($manager->has('manager_key'), 'Cache manager has operation');
        $this->assert($manager->delete('manager_key'), 'Cache manager delete operation');

        // Driver switching
        $fileDriver = $manager->driver('file');
        $memoryDriver = $manager->driver('memory');
        $this->assert($fileDriver->getDriverName() === 'file', 'Cache manager file driver access');
        $this->assert($memoryDriver->getDriverName() === 'memory', 'Cache manager memory driver access');

        // Remember functionality
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
            return 'callback_result';
        };

        $result1 = $manager->remember('remember_key', $callback);
        $result2 = $manager->remember('remember_key', $callback);
        
        $this->assert($result1 === 'callback_result', 'Cache manager remember first call');
        $this->assert($result2 === 'callback_result', 'Cache manager remember second call');
        $this->assert($callCount === 1, 'Cache manager remember callback called once');

        // Statistics
        $stats = $manager->getStats();
        $this->assert(isset($stats['manager']), 'Cache manager statistics structure');
        $this->assert(isset($stats['drivers']), 'Cache manager driver statistics');

        echo "Cache Manager tests completed.\n\n";
    }

    /**
     * Test Cache Manager Fallback
     */
    private function testCacheManagerFallback(): void
    {
        echo "Testing Cache Manager Fallback...\n";

        $config = [
            'default' => 'file',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'cache_dir' => '/invalid/path', // This will cause errors
                    'tag_dir' => '/invalid/path/tags'
                ],
                'memory' => [
                    'driver' => 'memory',
                    'max_memory' => 1024 * 1024
                ]
            ],
            'fallback_enabled' => true,
            'fallback_driver' => 'memory'
        ];

        $manager = new CacheManager($config);

        // Test fallback on failed operations
        $result = $manager->get('fallback_key', 'default_value');
        $this->assert($result === 'default_value', 'Cache manager fallback get operation');

        echo "Cache Manager Fallback tests completed.\n\n";
    }

    /**
     * Test Cache Manager Replication
     */
    private function testCacheManagerReplication(): void
    {
        echo "Testing Cache Manager Replication...\n";

        $config = [
            'default' => 'file',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'cache_dir' => $this->tempDir,
                    'tag_dir' => $this->tempDir . '/tags'
                ],
                'memory' => [
                    'driver' => 'memory',
                    'max_memory' => 1024 * 1024
                ]
            ],
            'replication' => true
        ];

        $manager = new CacheManager($config);

        // Set data (should replicate to both drivers)
        $this->assert($manager->set('replicated_key', 'replicated_value'), 'Cache manager replication set');

        // Check both drivers have the data
        $fileDriver = $manager->driver('file');
        $memoryDriver = $manager->driver('memory');
        
        $this->assert($fileDriver->get('replicated_key') === 'replicated_value', 'Cache manager replication file driver');
        $this->assert($memoryDriver->get('replicated_key') === 'replicated_value', 'Cache manager replication memory driver');

        echo "Cache Manager Replication tests completed.\n\n";
    }

    /**
     * Test Cache Service Provider
     */
    private function testCacheServiceProvider(): void
    {
        echo "Testing Cache Service Provider...\n";

        $container = Container::getInstance();
        $provider = new CacheServiceProvider($container);

        // Register services
        $provider->register();
        
        // Test service registration
        $this->assert($container->bound('cache'), 'Cache service provider cache binding');
        $this->assert($container->bound('cache.driver.file'), 'Cache service provider file driver binding');
        $this->assert($container->bound('cache.driver.memory'), 'Cache service provider memory driver binding');
        $this->assert($container->bound('cache.stats'), 'Cache service provider stats binding');
        $this->assert($container->bound('cache.cleaner'), 'Cache service provider cleaner binding');

        // Test service resolution
        $cache = $container->resolve('cache');
        $this->assert($cache instanceof CacheManager, 'Cache service provider cache resolution');

        $stats = $container->resolve('cache.stats');
        $this->assert(is_object($stats), 'Cache service provider stats resolution');

        $cleaner = $container->resolve('cache.cleaner');
        $this->assert(is_object($cleaner), 'Cache service provider cleaner resolution');

        echo "Cache Service Provider tests completed.\n\n";
    }

    /**
     * Test Cache Integration
     */
    private function testCacheIntegration(): void
    {
        echo "Testing Cache Integration...\n";

        // Test integration with existing Cache class
        $oldValue = Cache::get('integration_key');
        $this->assert($oldValue === null, 'Cache integration initial get');

        Cache::set('integration_key', 'integration_value');
        $newValue = Cache::get('integration_key');
        $this->assert($newValue === 'integration_value', 'Cache integration set and get');

        // Test remember functionality
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
            return 'remembered_value';
        };

        $result1 = Cache::remember('remember_integration_key', $callback);
        $result2 = Cache::remember('remember_integration_key', $callback);
        
        $this->assert($result1 === 'remembered_value', 'Cache integration remember first call');
        $this->assert($result2 === 'remembered_value', 'Cache integration remember second call');
        $this->assert($callCount === 1, 'Cache integration remember callback called once');

        echo "Cache Integration tests completed.\n\n";
    }

    /**
     * Test Cache Performance
     */
    private function testCachePerformance(): void
    {
        echo "Testing Cache Performance...\n";

        $config = [
            'default' => 'memory',
            'drivers' => [
                'memory' => [
                    'driver' => 'memory',
                    'max_memory' => 10 * 1024 * 1024 // 10MB
                ]
            ]
        ];

        $manager = new CacheManager($config);

        // Performance test: 1000 set operations
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $manager->set("perf_key_{$i}", "perf_value_{$i}");
        }
        $setTime = microtime(true) - $startTime;

        // Performance test: 1000 get operations
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $manager->get("perf_key_{$i}");
        }
        $getTime = microtime(true) - $startTime;

        $this->assert($setTime < 1.0, 'Cache performance 1000 sets under 1 second');
        $this->assert($getTime < 0.5, 'Cache performance 1000 gets under 0.5 seconds');

        echo "Performance: 1000 sets in {$setTime}s, 1000 gets in {$getTime}s\n";
        echo "Cache Performance tests completed.\n\n";
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
        echo "Cache System Test Results:\n";
        echo "=========================\n";
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

    /**
     * Clean up test files
     */
    private function cleanup(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                } elseif (is_dir($file)) {
                    $subFiles = glob($file . '/*');
                    foreach ($subFiles as $subFile) {
                        if (is_file($subFile)) {
                            unlink($subFile);
                        }
                    }
                    rmdir($file);
                }
            }
            rmdir($this->tempDir);
        }
    }
}