<?php

/**
 * File Cache Driver
 *
 * Enhanced file-based cache driver with improved performance,
 * atomic operations, and better error handling.
 *
 * @package  AliveChMS\Core\Cache
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/AbstractCacheDriver.php';

class FileDriver extends AbstractCacheDriver
{
    private string $cacheDir;
    private string $tagDir;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        
        $this->cacheDir = $this->config['cache_dir'] ?? __DIR__ . '/../../cache/data';
        $this->tagDir = $this->config['tag_dir'] ?? __DIR__ . '/../../cache/tags';
        
        $this->ensureDirectories();
    }

    /**
     * Get default configuration
     */
    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'cache_dir' => __DIR__ . '/../../cache/data',
            'tag_dir' => __DIR__ . '/../../cache/tags',
            'file_permissions' => 0644,
            'dir_permissions' => 0755,
            'atomic_writes' => true,
            'lock_timeout' => 10
        ]);
    }

    /**
     * Get cached value
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            $this->fireEvent('miss', $key);
            return $default;
        }

        $data = $this->readCacheFile($file);
        
        if ($data === null) {
            $this->fireEvent('miss', $key);
            $this->delete($key); // Clean up corrupted file
            return $default;
        }

        // Check expiration
        if ($data['expires'] > 0 && time() > $data['expires']) {
            $this->fireEvent('miss', $key);
            $this->delete($key);
            return $default;
        }

        $this->fireEvent('hit', $key, $data['value']);
        return $data['value'];
    }

    /**
     * Store value in cache
     */
    public function set(string $key, $value, ?int $ttl = null, array $tags = []): bool
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        $file = $this->getFilePath($key);
        $expires = $ttl > 0 ? time() + $ttl : 0;

        $data = [
            'value' => $value,
            'expires' => $expires,
            'tags' => $tags,
            'created' => time(),
            'key' => $key
        ];

        $serialized = $this->serialize($data);

        if (!$this->isValidSize($serialized)) {
            error_log("Cache value too large for key: $key");
            return false;
        }

        $success = $this->writeCacheFile($file, $serialized);

        if ($success && !empty($tags)) {
            $this->indexTags($key, $tags);
        }

        if ($success) {
            $this->fireEvent('set', $key, $value);
        }

        return $success;
    }

    /**
     * Check if cache key exists
     */
    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return false;
        }

        $data = $this->readCacheFile($file);
        
        if ($data === null) {
            $this->delete($key);
            return false;
        }

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
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            $success = @unlink($file);
            if ($success) {
                $this->fireEvent('delete', $key);
                $this->removeFromTagIndexes($key);
            }
            return $success;
        }

        return true;
    }

    /**
     * Flush all cache entries
     */
    public function flush(): bool
    {
        $deleted = 0;
        $total = 0;

        // Clear cache files
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/cache_*');
            $total += count($files);
            
            foreach ($files as $file) {
                if (is_file($file) && @unlink($file)) {
                    $deleted++;
                }
            }
        }

        // Clear tag files
        if (is_dir($this->tagDir)) {
            $files = glob($this->tagDir . '/tag_*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        return $deleted === $total;
    }

    /**
     * Invalidate cache entries by tag
     */
    public function invalidateTag(string $tag): int
    {
        $indexFile = $this->getTagIndexPath($tag);

        if (!file_exists($indexFile)) {
            return 0;
        }

        $data = $this->readCacheFile($indexFile);
        if (!$data || !isset($data['keys']) || !is_array($data['keys'])) {
            @unlink($indexFile);
            return 0;
        }

        $deleted = 0;
        foreach ($data['keys'] as $key) {
            if ($this->delete($key)) {
                $deleted++;
            }
        }

        @unlink($indexFile);
        return $deleted;
    }

    /**
     * Clean up expired cache entries
     */
    public function cleanup(): int
    {
        if (!is_dir($this->cacheDir)) {
            return 0;
        }

        $deleted = 0;
        $files = glob($this->cacheDir . '/cache_*');
        $now = time();

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $data = $this->readCacheFile($file);

            if ($data === null) {
                @unlink($file);
                $deleted++;
                continue;
            }

            // Delete if expired
            if (isset($data['expires']) && $data['expires'] > 0 && $now > $data['expires']) {
                @unlink($file);
                $deleted++;
                
                // Remove from tag indexes
                if (isset($data['key'])) {
                    $this->removeFromTagIndexes($data['key']);
                }
            }
        }

        return $deleted;
    }

    /**
     * Get driver name
     */
    public function getDriverName(): string
    {
        return 'file';
    }

    /**
     * Get driver-specific statistics
     */
    protected function getDriverSpecificStats(): array
    {
        $stats = [
            'cache_dir' => $this->cacheDir,
            'total_entries' => 0,
            'total_size' => 0,
            'expired_entries' => 0
        ];

        if (!is_dir($this->cacheDir)) {
            return $stats;
        }

        $files = glob($this->cacheDir . '/cache_*');
        $now = time();
        $totalSize = 0;
        $expired = 0;

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $size = filesize($file);
            $totalSize += $size;

            $data = $this->readCacheFile($file);
            if ($data && isset($data['expires']) && $data['expires'] > 0 && $now > $data['expires']) {
                $expired++;
            }
        }

        $stats['total_entries'] = count($files);
        $stats['total_size'] = $totalSize;
        $stats['total_size_mb'] = round($totalSize / 1024 / 1024, 2);
        $stats['expired_entries'] = $expired;

        return $stats;
    }

    /**
     * Ensure cache directories exist
     */
    private function ensureDirectories(): void
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, $this->config['dir_permissions'], true);
        }
        
        if (!is_dir($this->tagDir)) {
            @mkdir($this->tagDir, $this->config['dir_permissions'], true);
        }
    }

    /**
     * Get file path for cache key
     */
    private function getFilePath(string $key): string
    {
        $hash = $this->hashKey($key);
        return $this->cacheDir . '/cache_' . $hash;
    }

    /**
     * Get tag index file path
     */
    private function getTagIndexPath(string $tag): string
    {
        $hash = $this->hashKey('tag_' . $tag);
        return $this->tagDir . '/tag_' . $hash;
    }

    /**
     * Read cache file with error handling
     */
    private function readCacheFile(string $file): ?array
    {
        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = $this->unserialize($content);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Write cache file atomically
     */
    private function writeCacheFile(string $file, string $content): bool
    {
        if ($this->config['atomic_writes']) {
            return $this->atomicWrite($file, $content);
        }

        return @file_put_contents($file, $content, LOCK_EX) !== false;
    }

    /**
     * Atomic file write using temporary file
     */
    private function atomicWrite(string $file, string $content): bool
    {
        $tempFile = $file . '.tmp.' . uniqid();
        
        if (@file_put_contents($tempFile, $content, LOCK_EX) === false) {
            return false;
        }

        if (@chmod($tempFile, $this->config['file_permissions']) === false) {
            @unlink($tempFile);
            return false;
        }

        if (@rename($tempFile, $file) === false) {
            @unlink($tempFile);
            return false;
        }

        return true;
    }

    /**
     * Index cache key by tags
     */
    private function indexTags(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $indexFile = $this->getTagIndexPath($tag);
            $keys = [];

            if (file_exists($indexFile)) {
                $data = $this->readCacheFile($indexFile);
                if ($data && isset($data['keys']) && is_array($data['keys'])) {
                    $keys = $data['keys'];
                }
            }

            if (!in_array($key, $keys, true)) {
                $keys[] = $key;
                $indexData = [
                    'keys' => $keys,
                    'created' => time(),
                    'expires' => 0
                ];
                
                $serialized = $this->serialize($indexData);
                $this->writeCacheFile($indexFile, $serialized);
            }
        }
    }

    /**
     * Remove key from tag indexes
     */
    private function removeFromTagIndexes(string $key): void
    {
        if (!is_dir($this->tagDir)) {
            return;
        }

        $tagFiles = glob($this->tagDir . '/tag_*');
        
        foreach ($tagFiles as $tagFile) {
            $data = $this->readCacheFile($tagFile);
            
            if (!$data || !isset($data['keys']) || !is_array($data['keys'])) {
                continue;
            }

            $keyIndex = array_search($key, $data['keys'], true);
            if ($keyIndex !== false) {
                unset($data['keys'][$keyIndex]);
                $data['keys'] = array_values($data['keys']); // Re-index array
                
                if (empty($data['keys'])) {
                    @unlink($tagFile);
                } else {
                    $serialized = $this->serialize($data);
                    $this->writeCacheFile($tagFile, $serialized);
                }
            }
        }
    }
}