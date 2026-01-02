<?php

/**
 * Memory Cache Driver
 *
 * In-memory cache driver for ultra-fast access during request lifecycle.
 * Data is lost when the request ends, making it ideal for temporary caching.
 *
 * @package  AliveChMS\Core\Cache
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/AbstractCacheDriver.php';

class MemoryDriver extends AbstractCacheDriver
{
    private array $cache = [];
    private array $tags = [];
    private int $maxMemory;
    private int $currentMemory = 0;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->maxMemory = $this->config['max_memory'];
    }

    /**
     * Get default configuration
     */
    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'max_memory' => 50 * 1024 * 1024, // 50MB
            'serialize' => false, // No need to serialize in memory
            'compress' => false,  // No need to compress in memory
            'eviction_policy' => 'lru' // lru, fifo, random
        ]);
    }

    /**
     * Get cached value
     */
    public function get(string $key, $default = null)
    {
        if (!isset($this->cache[$key])) {
            $this->fireEvent('miss', $key);
            return $default;
        }

        $data = $this->cache[$key];

        // Check expiration
        if ($data['expires'] > 0 && time() > $data['expires']) {
            $this->delete($key);
            $this->fireEvent('miss', $key);
            return $default;
        }

        // Update access time for LRU
        $data['accessed'] = time();
        $this->cache[$key] = $data;

        $this->fireEvent('hit', $key, $data['value']);
        return $data['value'];
    }

    /**
     * Store value in cache
     */
    public function set(string $key, $value, ?int $ttl = null, array $tags = []): bool
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        $expires = $ttl > 0 ? time() + $ttl : 0;

        // Estimate memory usage
        $estimatedSize = $this->estimateSize($value);
        
        // Check if we need to evict entries
        if ($this->currentMemory + $estimatedSize > $this->maxMemory) {
            $this->evictEntries($estimatedSize);
        }

        // Remove old entry if exists
        if (isset($this->cache[$key])) {
            $this->currentMemory -= $this->cache[$key]['size'];
            $this->removeFromTags($key);
        }

        $data = [
            'value' => $value,
            'expires' => $expires,
            'tags' => $tags,
            'created' => time(),
            'accessed' => time(),
            'size' => $estimatedSize
        ];

        $this->cache[$key] = $data;
        $this->currentMemory += $estimatedSize;

        // Index tags
        if (!empty($tags)) {
            $this->indexTags($key, $tags);
        }

        $this->fireEvent('set', $key, $value);
        return true;
    }

    /**
     * Get multiple cached values (optimized for memory driver)
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        $now = time();
        
        foreach ($keys as $key) {
            if (!isset($this->cache[$key])) {
                $this->fireEvent('miss', $key);
                $result[$key] = null;
                continue;
            }

            $data = $this->cache[$key];

            // Check expiration
            if ($data['expires'] > 0 && $now > $data['expires']) {
                $this->delete($key);
                $this->fireEvent('miss', $key);
                $result[$key] = null;
                continue;
            }

            // Update access time for LRU
            $data['accessed'] = $now;
            $this->cache[$key] = $data;

            $this->fireEvent('hit', $key, $data['value']);
            $result[$key] = $data['value'];
        }

        return $result;
    }

    /**
     * Store multiple values in cache (optimized for memory driver)
     */
    public function setMultiple(array $values, ?int $ttl = null, array $tags = []): bool
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        $expires = $ttl > 0 ? time() + $ttl : 0;
        $now = time();

        // Calculate total size needed
        $totalSize = 0;
        foreach ($values as $value) {
            $totalSize += $this->estimateSize($value);
        }

        // Check if we need to evict entries
        if ($this->currentMemory + $totalSize > $this->maxMemory) {
            $this->evictEntries($totalSize);
        }

        foreach ($values as $key => $value) {
            // Remove old entry if exists
            if (isset($this->cache[$key])) {
                $this->currentMemory -= $this->cache[$key]['size'];
                $this->removeFromTags($key);
            }

            $estimatedSize = $this->estimateSize($value);
            
            $data = [
                'value' => $value,
                'expires' => $expires,
                'tags' => $tags,
                'created' => $now,
                'accessed' => $now,
                'size' => $estimatedSize
            ];

            $this->cache[$key] = $data;
            $this->currentMemory += $estimatedSize;

            // Index tags
            if (!empty($tags)) {
                $this->indexTags($key, $tags);
            }

            $this->fireEvent('set', $key, $value);
        }

        return true;
    }

    /**
     * Check if cache key exists
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $data = $this->cache[$key];

        // Check expiration
        if ($data['expires'] > 0 && time() > $data['expires']) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        if (isset($this->cache[$key])) {
            $this->currentMemory -= $this->cache[$key]['size'];
            $this->removeFromTags($key);
            unset($this->cache[$key]);
            $this->fireEvent('delete', $key);
        }

        return true;
    }

    /**
     * Flush all cache entries
     */
    public function flush(): bool
    {
        $this->cache = [];
        $this->tags = [];
        $this->currentMemory = 0;
        return true;
    }

    /**
     * Invalidate cache entries by tag
     */
    public function invalidateTag(string $tag): int
    {
        if (!isset($this->tags[$tag])) {
            return 0;
        }

        $deleted = 0;
        foreach ($this->tags[$tag] as $key) {
            if ($this->delete($key)) {
                $deleted++;
            }
        }

        unset($this->tags[$tag]);
        return $deleted;
    }

    /**
     * Clean up expired cache entries
     */
    public function cleanup(): int
    {
        $deleted = 0;
        $now = time();

        foreach ($this->cache as $key => $data) {
            if ($data['expires'] > 0 && $now > $data['expires']) {
                $this->delete($key);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get driver name
     */
    public function getDriverName(): string
    {
        return 'memory';
    }

    /**
     * Get driver-specific statistics
     */
    protected function getDriverSpecificStats(): array
    {
        $expired = 0;
        $now = time();

        foreach ($this->cache as $data) {
            if ($data['expires'] > 0 && $now > $data['expires']) {
                $expired++;
            }
        }

        return [
            'total_entries' => count($this->cache),
            'memory_used' => $this->currentMemory,
            'memory_used_mb' => round($this->currentMemory / 1024 / 1024, 2),
            'max_memory' => $this->maxMemory,
            'max_memory_mb' => round($this->maxMemory / 1024 / 1024, 2),
            'memory_usage_percent' => round(($this->currentMemory / $this->maxMemory) * 100, 2),
            'expired_entries' => $expired,
            'tag_count' => count($this->tags)
        ];
    }

    /**
     * Estimate memory size of a value
     */
    private function estimateSize($value): int
    {
        if (is_string($value)) {
            return strlen($value) + 24; // String overhead
        } elseif (is_array($value)) {
            return strlen(serialize($value)) + 48; // Array overhead
        } elseif (is_object($value)) {
            return strlen(serialize($value)) + 64; // Object overhead
        } elseif (is_int($value) || is_float($value)) {
            return 16; // Numeric overhead
        } elseif (is_bool($value)) {
            return 8; // Boolean overhead
        }

        return 32; // Default overhead
    }

    /**
     * Evict entries to make room for new data
     */
    private function evictEntries(int $neededSize): void
    {
        $policy = $this->config['eviction_policy'];
        $toEvict = [];

        switch ($policy) {
            case 'lru':
                $toEvict = $this->getLRUCandidates($neededSize);
                break;
            case 'fifo':
                $toEvict = $this->getFIFOCandidates($neededSize);
                break;
            case 'random':
                $toEvict = $this->getRandomCandidates($neededSize);
                break;
        }

        foreach ($toEvict as $key) {
            $this->delete($key);
        }
    }

    /**
     * Get LRU (Least Recently Used) eviction candidates
     */
    private function getLRUCandidates(int $neededSize): array
    {
        $candidates = [];
        $freedSize = 0;

        // Sort by access time (oldest first)
        $sorted = $this->cache;
        uasort($sorted, function ($a, $b) {
            return $a['accessed'] <=> $b['accessed'];
        });

        foreach ($sorted as $key => $data) {
            $candidates[] = $key;
            $freedSize += $data['size'];
            
            if ($freedSize >= $neededSize) {
                break;
            }
        }

        return $candidates;
    }

    /**
     * Get FIFO (First In, First Out) eviction candidates
     */
    private function getFIFOCandidates(int $neededSize): array
    {
        $candidates = [];
        $freedSize = 0;

        // Sort by creation time (oldest first)
        $sorted = $this->cache;
        uasort($sorted, function ($a, $b) {
            return $a['created'] <=> $b['created'];
        });

        foreach ($sorted as $key => $data) {
            $candidates[] = $key;
            $freedSize += $data['size'];
            
            if ($freedSize >= $neededSize) {
                break;
            }
        }

        return $candidates;
    }

    /**
     * Get random eviction candidates
     */
    private function getRandomCandidates(int $neededSize): array
    {
        $candidates = [];
        $freedSize = 0;
        $keys = array_keys($this->cache);
        shuffle($keys);

        foreach ($keys as $key) {
            $candidates[] = $key;
            $freedSize += $this->cache[$key]['size'];
            
            if ($freedSize >= $neededSize) {
                break;
            }
        }

        return $candidates;
    }

    /**
     * Index cache key by tags
     */
    private function indexTags(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }
            
            if (!in_array($key, $this->tags[$tag], true)) {
                $this->tags[$tag][] = $key;
            }
        }
    }

    /**
     * Remove key from tag indexes
     */
    private function removeFromTags(string $key): void
    {
        if (!isset($this->cache[$key]) || empty($this->cache[$key]['tags'])) {
            return;
        }

        foreach ($this->cache[$key]['tags'] as $tag) {
            if (isset($this->tags[$tag])) {
                $index = array_search($key, $this->tags[$tag], true);
                if ($index !== false) {
                    unset($this->tags[$tag][$index]);
                    $this->tags[$tag] = array_values($this->tags[$tag]); // Re-index
                    
                    if (empty($this->tags[$tag])) {
                        unset($this->tags[$tag]);
                    }
                }
            }
        }
    }
}