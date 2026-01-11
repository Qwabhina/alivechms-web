<?php

/**
 * Cache Manager
 *
 * Manages multiple cache drivers and provides a unified interface
 * for caching operations. Supports driver switching, fallbacks,
 * and distributed caching strategies.
 *
 * @package  AliveChMS\Core\Cache
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/CacheInterface.php';
require_once __DIR__ . '/FileDriver.php';
require_once __DIR__ . '/MemoryDriver.php';

class CacheManager implements CacheInterface
{
    private array $drivers = [];
    private array $config;
    private string $defaultDriver;
    private array $driverInstances = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->defaultDriver = $this->config['default'];
        $this->registerDefaultDrivers();
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'default' => 'file',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'cache_dir' => __DIR__ . '/../../cache/data',
                    'tag_dir' => __DIR__ . '/../../cache/tags'
                ],
                'memory' => [
                    'driver' => 'memory',
                    'max_memory' => 50 * 1024 * 1024 // 50MB
                ]
            ],
            'fallback_enabled' => true,
            'fallback_driver' => 'memory',
            'distributed' => false,
            'replication' => false
        ];
    }

    /**
     * Register default drivers
     */
    private function registerDefaultDrivers(): void
    {
        $this->drivers['file'] = FileDriver::class;
        $this->drivers['memory'] = MemoryDriver::class;
    }

    /**
     * Register a custom cache driver
     */
    public function extend(string $name, string $driverClass): void
    {
        if (!is_subclass_of($driverClass, CacheInterface::class)) {
            throw new InvalidArgumentException("Driver must implement CacheInterface");
        }

        $this->drivers[$name] = $driverClass;
    }

    /**
     * Get cache driver instance
     */
    public function driver(string $name = null): CacheInterface
    {
        $name = $name ?: $this->defaultDriver;

        if (!isset($this->driverInstances[$name])) {
            $this->driverInstances[$name] = $this->createDriver($name);
        }

        return $this->driverInstances[$name];
    }

    /**
     * Create driver instance
     */
    private function createDriver(string $name): CacheInterface
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Cache driver [{$name}] not found");
        }

        if (!isset($this->config['drivers'][$name])) {
            throw new InvalidArgumentException("Configuration for cache driver [{$name}] not found");
        }

        $driverClass = $this->drivers[$name];
        $config = $this->config['drivers'][$name];

        return new $driverClass($config);
    }

    /**
     * Get cached value with fallback support
     */
    public function get(string $key, $default = null)
    {
        try {
            $value = $this->driver()->get($key, $default);
            
            // If value not found and fallback enabled, try fallback driver
            if ($value === $default && $this->config['fallback_enabled']) {
                $fallbackDriver = $this->config['fallback_driver'];
                if ($fallbackDriver !== $this->defaultDriver) {
                    $value = $this->driver($fallbackDriver)->get($key, $default);
                }
            }
            
            return $value;
        } catch (Exception $e) {
            error_log("Cache get error: " . $e->getMessage());
            return $this->handleFallback('get', [$key, $default], $default);
        }
    }

    /**
     * Store value in cache with replication support
     */
    public function set(string $key, $value, int $ttl = 3600, array $tags = []): bool
    {
        try {
            $success = $this->driver()->set($key, $value, $ttl, $tags);
            
            // Replicate to other drivers if enabled
            if ($success && $this->config['replication']) {
                $this->replicateSet($key, $value, $ttl, $tags);
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Cache set error: " . $e->getMessage());
            return $this->handleFallback('set', [$key, $value, $ttl, $tags], false);
        }
    }

    /**
     * Get multiple cached values
     */
    public function getMultiple(array $keys): array
    {
        try {
            return $this->driver()->getMultiple($keys);
        } catch (Exception $e) {
            error_log("Cache getMultiple error: " . $e->getMessage());
            return $this->handleFallback('getMultiple', [$keys], []);
        }
    }

    /**
     * Store multiple values in cache
     */
    public function setMultiple(array $values, int $ttl = 3600, array $tags = []): bool
    {
        try {
            $success = $this->driver()->setMultiple($values, $ttl, $tags);
            
            if ($success && $this->config['replication']) {
                $this->replicateSetMultiple($values, $ttl, $tags);
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Cache setMultiple error: " . $e->getMessage());
            return $this->handleFallback('setMultiple', [$values, $ttl, $tags], false);
        }
    }

    /**
     * Check if cache key exists
     */
    public function has(string $key): bool
    {
        try {
            return $this->driver()->has($key);
        } catch (Exception $e) {
            error_log("Cache has error: " . $e->getMessage());
            return $this->handleFallback('has', [$key], false);
        }
    }

    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        try {
            $success = $this->driver()->delete($key);
            
            // Delete from replicated drivers
            if ($this->config['replication']) {
                $this->replicateDelete($key);
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Cache delete error: " . $e->getMessage());
            return $this->handleFallback('delete', [$key], false);
        }
    }

    /**
     * Delete multiple cached values
     */
    public function deleteMultiple(array $keys): bool
    {
        try {
            $success = $this->driver()->deleteMultiple($keys);
            
            if ($this->config['replication']) {
                $this->replicateDeleteMultiple($keys);
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Cache deleteMultiple error: " . $e->getMessage());
            return $this->handleFallback('deleteMultiple', [$keys], false);
        }
    }

    /**
     * Flush all cache entries
     */
    public function flush(): bool
    {
        try {
            $success = $this->driver()->flush();
            
            // Flush all drivers if replication enabled
            if ($this->config['replication']) {
                foreach ($this->config['drivers'] as $driverName => $config) {
                    if ($driverName !== $this->defaultDriver) {
                        try {
                            $this->driver($driverName)->flush();
                        } catch (Exception $e) {
                            error_log("Failed to flush driver [{$driverName}]: " . $e->getMessage());
                        }
                    }
                }
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Cache flush error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidate cache entries by tag
     */
    public function invalidateTag(string $tag): int
    {
        try {
            $count = $this->driver()->invalidateTag($tag);
            
            // Invalidate on replicated drivers
            if ($this->config['replication']) {
                foreach ($this->config['drivers'] as $driverName => $config) {
                    if ($driverName !== $this->defaultDriver) {
                        try {
                            $this->driver($driverName)->invalidateTag($tag);
                        } catch (Exception $e) {
                            error_log("Failed to invalidate tag on driver [{$driverName}]: " . $e->getMessage());
                        }
                    }
                }
            }
            
            return $count;
        } catch (Exception $e) {
            error_log("Cache invalidateTag error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Invalidate multiple tags
     */
    public function invalidateTags(array $tags): int
    {
        $total = 0;
        foreach ($tags as $tag) {
            $total += $this->invalidateTag($tag);
        }
        return $total;
    }

    /**
     * Get cache statistics from all drivers
     */
    public function getStats(): array
    {
        $stats = [
            'manager' => [
                'default_driver' => $this->defaultDriver,
                'available_drivers' => array_keys($this->drivers),
                'active_drivers' => array_keys($this->driverInstances),
                'fallback_enabled' => $this->config['fallback_enabled'],
                'replication_enabled' => $this->config['replication']
            ],
            'drivers' => []
        ];

        foreach ($this->driverInstances as $name => $driver) {
            try {
                $stats['drivers'][$name] = $driver->getStats();
            } catch (Exception $e) {
                $stats['drivers'][$name] = ['error' => $e->getMessage()];
            }
        }

        return $stats;
    }

    /**
     * Clean up expired cache entries on all drivers
     */
    public function cleanup(): int
    {
        $total = 0;
        
        foreach ($this->driverInstances as $name => $driver) {
            try {
                $total += $driver->cleanup();
            } catch (Exception $e) {
                error_log("Cleanup error on driver [{$name}]: " . $e->getMessage());
            }
        }
        
        return $total;
    }

    /**
     * Get driver name
     */
    public function getDriverName(): string
    {
        return 'manager';
    }

    /**
     * Remember: Get from cache or execute callback and cache result
     */
    public function remember(string $key, callable $callback, int $ttl = 3600, array $tags = []): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl, $tags);

        return $value;
    }

    /**
     * Remember forever
     */
    public function rememberForever(string $key, callable $callback, array $tags = []): mixed
    {
        return $this->remember($key, $callback, 0, $tags);
    }

    /**
     * Warm cache with predefined data
     */
    public function warm(array $data, int $ttl = 3600, array $tags = []): bool
    {
        return $this->setMultiple($data, $ttl, $tags);
    }

    /**
     * Handle fallback operations
     */
    private function handleFallback(string $method, array $args, $default)
    {
        if (!$this->config['fallback_enabled']) {
            return $default;
        }

        $fallbackDriver = $this->config['fallback_driver'];
        if ($fallbackDriver === $this->defaultDriver) {
            return $default;
        }

        try {
            return $this->driver($fallbackDriver)->$method(...$args);
        } catch (Exception $e) {
            error_log("Fallback driver error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Replicate set operation to other drivers
     */
    private function replicateSet(string $key, $value, int $ttl, array $tags): void
    {
        foreach ($this->config['drivers'] as $driverName => $config) {
            if ($driverName !== $this->defaultDriver) {
                try {
                    $this->driver($driverName)->set($key, $value, $ttl, $tags);
                } catch (Exception $e) {
                    error_log("Replication error on driver [{$driverName}]: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Replicate setMultiple operation
     */
    private function replicateSetMultiple(array $values, int $ttl, array $tags): void
    {
        foreach ($this->config['drivers'] as $driverName => $config) {
            if ($driverName !== $this->defaultDriver) {
                try {
                    $this->driver($driverName)->setMultiple($values, $ttl, $tags);
                } catch (Exception $e) {
                    error_log("Replication error on driver [{$driverName}]: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Replicate delete operation
     */
    private function replicateDelete(string $key): void
    {
        foreach ($this->config['drivers'] as $driverName => $config) {
            if ($driverName !== $this->defaultDriver) {
                try {
                    $this->driver($driverName)->delete($key);
                } catch (Exception $e) {
                    error_log("Replication error on driver [{$driverName}]: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Replicate deleteMultiple operation
     */
    private function replicateDeleteMultiple(array $keys): void
    {
        foreach ($this->config['drivers'] as $driverName => $config) {
            if ($driverName !== $this->defaultDriver) {
                try {
                    $this->driver($driverName)->deleteMultiple($keys);
                } catch (Exception $e) {
                    error_log("Replication error on driver [{$driverName}]: " . $e->getMessage());
                }
            }
        }
    }
}