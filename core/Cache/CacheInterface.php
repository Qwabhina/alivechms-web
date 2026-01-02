<?php

/**
 * Cache Interface
 *
 * Defines the contract for cache drivers in the AliveChMS caching system.
 * Provides a consistent API across different cache implementations.
 *
 * @package  AliveChMS\Core\Cache
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

interface CacheInterface
{
    /**
     * Get cached value
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     */
    public function get(string $key, $default = null);

    /**
     * Store value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (0 = forever)
     * @param array $tags Cache tags for group invalidation
     * @return bool Success status
     */
    public function set(string $key, $value, int $ttl = 3600, array $tags = []): bool;

    /**
     * Get multiple cached values
     *
     * @param array $keys Array of cache keys
     * @return array Key-value pairs of cached data
     */
    public function getMultiple(array $keys): array;

    /**
     * Store multiple values in cache
     *
     * @param array $values Key-value pairs to cache
     * @param int $ttl Time to live in seconds
     * @param array $tags Cache tags
     * @return bool Success status
     */
    public function setMultiple(array $values, int $ttl = 3600, array $tags = []): bool;

    /**
     * Check if cache key exists
     *
     * @param string $key Cache key
     * @return bool Whether key exists
     */
    public function has(string $key): bool;

    /**
     * Delete cached value
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete(string $key): bool;

    /**
     * Delete multiple cached values
     *
     * @param array $keys Array of cache keys
     * @return bool Success status
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Flush all cache entries
     *
     * @return bool Success status
     */
    public function flush(): bool;

    /**
     * Invalidate cache entries by tag
     *
     * @param string $tag Tag name
     * @return int Number of entries invalidated
     */
    public function invalidateTag(string $tag): int;

    /**
     * Invalidate multiple tags
     *
     * @param array $tags Array of tag names
     * @return int Total number of entries invalidated
     */
    public function invalidateTags(array $tags): int;

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getStats(): array;

    /**
     * Clean up expired cache entries
     *
     * @return int Number of expired entries deleted
     */
    public function cleanup(): int;

    /**
     * Get cache driver name
     *
     * @return string Driver name
     */
    public function getDriverName(): string;
}