<?php

/**
 * Cache Service Provider
 *
 * Registers cache services and drivers in the dependency injection container.
 * Configures the cache manager and provides cache-related services.
 *
 * @package  AliveChMS\Core\Providers
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../ServiceProvider.php';
require_once __DIR__ . '/../Cache/CacheManager.php';
require_once __DIR__ . '/../Cache/FileDriver.php';
require_once __DIR__ . '/../Cache/MemoryDriver.php';

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register cache services
     */
    public function register(): void
    {
        // Register cache manager
        $this->container->singleton('cache', function ($container) {
            $config = $this->getCacheConfig();
            return new CacheManager($config);
        });

        // Register cache manager alias
        $this->container->alias('cache', CacheManager::class);

        // Register individual drivers
        $this->registerDrivers();

        // Register cache-related services
        $this->registerCacheServices();
    }

    /**
     * Boot cache services
     */
    public function boot(): void
    {
        // Set up cache event listeners
        $this->setupEventListeners();

        // Perform cache warming if configured
        $this->warmCache();

        // Schedule cleanup if configured
        $this->scheduleCleanup();
    }

    /**
     * Register cache drivers
     */
    private function registerDrivers(): void
    {
        // File driver
        $this->container->bind('cache.driver.file', function ($container) {
            return new FileDriver($this->getFileDriverConfig());
        });

        // Memory driver
        $this->container->bind('cache.driver.memory', function ($container) {
            return new MemoryDriver($this->getMemoryDriverConfig());
        });
    }

    /**
     * Register cache-related services
     */
    private function registerCacheServices(): void
    {
        // Cache statistics service
        $this->container->bind('cache.stats', function ($container) {
            return new class($container->resolve('cache')) {
                private CacheManager $cache;

                public function __construct(CacheManager $cache)
                {
                    $this->cache = $cache;
                }

                public function getStats(): array
                {
                    return $this->cache->getStats();
                }

                public function getFormattedStats(): string
                {
                    $stats = $this->getStats();
                    $output = "Cache Statistics:\n";
                    $output .= "================\n";
                    
                    foreach ($stats['drivers'] as $name => $driverStats) {
                        $output .= "\n{$name} Driver:\n";
                        $output .= "  Hits: " . ($driverStats['hits'] ?? 0) . "\n";
                        $output .= "  Misses: " . ($driverStats['misses'] ?? 0) . "\n";
                        $output .= "  Hit Ratio: " . ($driverStats['hit_ratio'] ?? 0) . "%\n";
                        
                        if (isset($driverStats['total_entries'])) {
                            $output .= "  Entries: " . $driverStats['total_entries'] . "\n";
                        }
                        
                        if (isset($driverStats['memory_used_mb'])) {
                            $output .= "  Memory: " . $driverStats['memory_used_mb'] . " MB\n";
                        }
                    }
                    
                    return $output;
                }
            };
        });

        // Cache cleaner service
        $this->container->bind('cache.cleaner', function ($container) {
            return new class($container->resolve('cache')) {
                private CacheManager $cache;

                public function __construct(CacheManager $cache)
                {
                    $this->cache = $cache;
                }

                public function cleanup(): int
                {
                    return $this->cache->cleanup();
                }

                public function cleanupExpired(): int
                {
                    return $this->cleanup();
                }

                public function flush(): bool
                {
                    return $this->cache->flush();
                }
            };
        });
    }

    /**
     * Set up cache event listeners
     */
    private function setupEventListeners(): void
    {
        if (!$this->container->bound('events')) {
            return; // Event system not available
        }

        $eventDispatcher = $this->container->resolve('events');

        // Listen for cache events and log them
        $eventDispatcher->listen('cache.*', function ($event) {
            if (method_exists($event, 'getKey')) {
                $key = $event->getKey();
                $eventName = $event->getName();
                error_log("Cache event: {$eventName} for key: {$key}");
            }
        });
    }

    /**
     * Warm cache with predefined data
     */
    private function warmCache(): void
    {
        $config = $this->getCacheConfig();
        
        if (!isset($config['warm']) || !$config['warm']['enabled']) {
            return;
        }

        $cache = $this->container->resolve('cache');
        $warmData = $config['warm']['data'] ?? [];

        if (!empty($warmData)) {
            $cache->warm($warmData, $config['warm']['ttl'] ?? 3600);
        }
    }

    /**
     * Schedule cache cleanup
     */
    private function scheduleCleanup(): void
    {
        $config = $this->getCacheConfig();
        
        if (!isset($config['cleanup']) || !$config['cleanup']['enabled']) {
            return;
        }

        // Register cleanup function to run at script end
        register_shutdown_function(function () {
            try {
                $cache = $this->container->resolve('cache');
                $cache->cleanup();
            } catch (Exception $e) {
                error_log("Cache cleanup error: " . $e->getMessage());
            }
        });
    }

    /**
     * Get cache configuration
     */
    private function getCacheConfig(): array
    {
        return [
            'default' => $_ENV['CACHE_DRIVER'] ?? 'file',
            'drivers' => [
                'file' => $this->getFileDriverConfig(),
                'memory' => $this->getMemoryDriverConfig()
            ],
            'fallback_enabled' => ($_ENV['CACHE_FALLBACK'] ?? 'true') === 'true',
            'fallback_driver' => $_ENV['CACHE_FALLBACK_DRIVER'] ?? 'memory',
            'distributed' => ($_ENV['CACHE_DISTRIBUTED'] ?? 'false') === 'true',
            'replication' => ($_ENV['CACHE_REPLICATION'] ?? 'false') === 'true',
            'warm' => [
                'enabled' => ($_ENV['CACHE_WARM'] ?? 'false') === 'true',
                'ttl' => (int)($_ENV['CACHE_WARM_TTL'] ?? 3600),
                'data' => []
            ],
            'cleanup' => [
                'enabled' => ($_ENV['CACHE_CLEANUP'] ?? 'true') === 'true',
                'interval' => (int)($_ENV['CACHE_CLEANUP_INTERVAL'] ?? 3600)
            ]
        ];
    }

    /**
     * Get file driver configuration
     */
    private function getFileDriverConfig(): array
    {
        return [
            'driver' => 'file',
            'cache_dir' => $_ENV['CACHE_FILE_DIR'] ?? __DIR__ . '/../../cache/data',
            'tag_dir' => $_ENV['CACHE_TAG_DIR'] ?? __DIR__ . '/../../cache/tags',
            'default_ttl' => (int)($_ENV['CACHE_DEFAULT_TTL'] ?? 3600),
            'max_size' => (int)($_ENV['CACHE_MAX_SIZE'] ?? 5242880), // 5MB
            'file_permissions' => octdec($_ENV['CACHE_FILE_PERMISSIONS'] ?? '644'),
            'dir_permissions' => octdec($_ENV['CACHE_DIR_PERMISSIONS'] ?? '755'),
            'atomic_writes' => ($_ENV['CACHE_ATOMIC_WRITES'] ?? 'true') === 'true',
            'lock_timeout' => (int)($_ENV['CACHE_LOCK_TIMEOUT'] ?? 10),
            'serialize' => ($_ENV['CACHE_SERIALIZE'] ?? 'true') === 'true',
            'compress' => ($_ENV['CACHE_COMPRESS'] ?? 'false') === 'true',
            'events' => ($_ENV['CACHE_EVENTS'] ?? 'true') === 'true'
        ];
    }

    /**
     * Get memory driver configuration
     */
    private function getMemoryDriverConfig(): array
    {
        return [
            'driver' => 'memory',
            'max_memory' => (int)($_ENV['CACHE_MEMORY_MAX'] ?? 50 * 1024 * 1024), // 50MB
            'default_ttl' => (int)($_ENV['CACHE_DEFAULT_TTL'] ?? 3600),
            'eviction_policy' => $_ENV['CACHE_EVICTION_POLICY'] ?? 'lru', // lru, fifo, random
            'serialize' => false, // No need to serialize in memory
            'compress' => false,  // No need to compress in memory
            'events' => ($_ENV['CACHE_EVENTS'] ?? 'true') === 'true'
        ];
    }

    /**
     * Get provided services
     */
    public function provides(): array
    {
        return [
            'cache',
            'cache.driver.file',
            'cache.driver.memory',
            'cache.stats',
            'cache.cleaner',
            CacheManager::class
        ];
    }
}