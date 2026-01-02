<?php

/**
 * Abstract Cache Driver
 *
 * Base implementation for cache drivers with common functionality
 * and event integration.
 *
 * @package  AliveChMS\Core\Cache
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/CacheInterface.php';
require_once __DIR__ . '/../Events/EventDispatcher.php';
require_once __DIR__ . '/../Events/SystemEvents.php';

abstract class AbstractCacheDriver implements CacheInterface
{
    protected array $config;
    protected ?EventDispatcher $eventDispatcher = null;
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->eventDispatcher = EventDispatcher::getInstance();
    }

    /**
     * Get default configuration
     *
     * @return array Default config
     */
    protected function getDefaultConfig(): array
    {
        return [
            'default_ttl' => 3600,
            'max_size' => 5242880, // 5MB
            'serialize' => true,
            'compress' => false,
            'events' => true
        ];
    }

    /**
     * Remember: Get from cache or execute callback and cache result
     *
     * @param string $key Cache key
     * @param callable $callback Callback to execute if cache miss
     * @param int $ttl Time to live
     * @param array $tags Cache tags
     * @return mixed Cached or fresh value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null, array $tags = []): mixed
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl, $tags);

        return $value;
    }

    /**
     * Remember forever: Get from cache or execute callback and cache result permanently
     *
     * @param string $key Cache key
     * @param callable $callback Callback to execute if cache miss
     * @param array $tags Cache tags
     * @return mixed Cached or fresh value
     */
    public function rememberForever(string $key, callable $callback, array $tags = []): mixed
    {
        return $this->remember($key, $callback, 0, $tags);
    }

    /**
     * Get multiple cached values
     *
     * @param array $keys Array of cache keys
     * @return array Key-value pairs of cached data
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * Store multiple values in cache
     *
     * @param array $values Key-value pairs to cache
     * @param int $ttl Time to live in seconds
     * @param array $tags Cache tags
     * @return bool Success status
     */
    public function setMultiple(array $values, ?int $ttl = null, array $tags = []): bool
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl, $tags)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Delete multiple cached values
     *
     * @param array $keys Array of cache keys
     * @return bool Success status
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Invalidate multiple tags
     *
     * @param array $tags Array of tag names
     * @return int Total number of entries invalidated
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
     * Increment cached value
     *
     * @param string $key Cache key
     * @param int $value Value to increment by
     * @return int|false New value or false on failure
     */
    public function increment(string $key, int $value = 1)
    {
        $current = $this->get($key, 0);
        if (!is_numeric($current)) {
            return false;
        }
        
        $new = (int)$current + $value;
        return $this->set($key, $new) ? $new : false;
    }

    /**
     * Decrement cached value
     *
     * @param string $key Cache key
     * @param int $value Value to decrement by
     * @return int|false New value or false on failure
     */
    public function decrement(string $key, int $value = 1)
    {
        return $this->increment($key, -$value);
    }

    /**
     * Get cache statistics including driver-specific stats
     *
     * @return array Cache statistics
     */
    public function getStats(): array
    {
        $baseStats = [
            'driver' => $this->getDriverName(),
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'sets' => $this->stats['sets'],
            'deletes' => $this->stats['deletes'],
            'hit_ratio' => $this->calculateHitRatio()
        ];

        return array_merge($baseStats, $this->getDriverSpecificStats());
    }

    /**
     * Calculate hit ratio
     *
     * @return float Hit ratio as percentage
     */
    protected function calculateHitRatio(): float
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        return $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0.0;
    }

    /**
     * Get driver-specific statistics
     *
     * @return array Driver-specific stats
     */
    protected function getDriverSpecificStats(): array
    {
        return [];
    }

    /**
     * Fire cache event
     *
     * @param string $eventType Event type (hit, miss, set, delete)
     * @param string $key Cache key
     * @param mixed $value Cache value (optional)
     */
    protected function fireEvent(string $eventType, string $key, $value = null): void
    {
        if (!$this->config['events'] || !$this->eventDispatcher) {
            return;
        }

        switch ($eventType) {
            case 'hit':
                $this->stats['hits']++;
                $this->eventDispatcher->dispatch(new CacheHitEvent($key, $value, $this->getDriverName()));
                break;
            case 'miss':
                $this->stats['misses']++;
                $this->eventDispatcher->dispatch(new CacheMissEvent($key, $this->getDriverName()));
                break;
            case 'set':
                $this->stats['sets']++;
                break;
            case 'delete':
                $this->stats['deletes']++;
                break;
        }
    }

    /**
     * Serialize value for storage
     *
     * @param mixed $value Value to serialize
     * @return string Serialized value
     */
    protected function serialize($value): string
    {
        if (!$this->config['serialize']) {
            return (string)$value;
        }

        $serialized = serialize($value);
        
        if ($this->config['compress'] && function_exists('gzcompress')) {
            $compressed = gzcompress($serialized);
            if ($compressed !== false && strlen($compressed) < strlen($serialized)) {
                return 'gz:' . $compressed;
            }
        }
        
        return $serialized;
    }

    /**
     * Unserialize value from storage
     *
     * @param string $value Serialized value
     * @return mixed Unserialized value
     */
    protected function unserialize(string $value)
    {
        if (!$this->config['serialize']) {
            return $value;
        }

        // Handle compressed data
        if (str_starts_with($value, 'gz:')) {
            $compressed = substr($value, 3);
            $value = gzuncompress($compressed);
            if ($value === false) {
                return null;
            }
        }

        $unserialized = @unserialize($value);
        return $unserialized !== false ? $unserialized : null;
    }

    /**
     * Check if value size is within limits
     *
     * @param string $serializedValue Serialized value
     * @return bool Whether size is acceptable
     */
    protected function isValidSize(string $serializedValue): bool
    {
        return strlen($serializedValue) <= $this->config['max_size'];
    }

    /**
     * Generate cache key hash
     *
     * @param string $key Original key
     * @return string Hashed key
     */
    protected function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }
}