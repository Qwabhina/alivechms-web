<?php

/**
 * Cache Manager (Legacy Compatibility Layer)
 *
 * Provides backward compatibility with the existing Cache class
 * while leveraging the new cache system architecture.
 *
 * @package  AliveChMS\Core
 * @version  3.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Cache/CacheManager.php';

class Cache
{
   private static ?CacheManager $manager = null;
   private const CACHE_DIR = __DIR__ . '/../cache/data';
   private const DEFAULT_TTL = 3600; // 1 hour
   private const MAX_FILE_SIZE = 5242880; // 5MB

   /**
    * Get cache manager instance
    */
   private static function getManager(): CacheManager
   {
      if (self::$manager === null) {
         $config = [
            'default' => 'file',
            'drivers' => [
               'file' => [
                  'driver' => 'file',
                  'cache_dir' => self::CACHE_DIR,
                  'tag_dir' => self::CACHE_DIR . '/../tags',
                  'default_ttl' => self::DEFAULT_TTL,
                  'max_size' => self::MAX_FILE_SIZE
               ],
               'memory' => [
                  'driver' => 'memory',
                  'max_memory' => 50 * 1024 * 1024 // 50MB
               ]
            ],
            'fallback_enabled' => true,
            'fallback_driver' => 'memory'
         ];

         self::$manager = new CacheManager($config);
      }

      return self::$manager;
   }

   /**
    * Get cached value
    * 
    * @param string $key Cache key
    * @param mixed $default Default value if not found
    * @return mixed Cached value or default
    */
   public static function get(string $key, $default = null)
   {
      return self::getManager()->get($key, $default);
   }

   /**
    * Store value in cache
    * 
    * @param string $key Cache key
    * @param mixed $value Value to cache
    * @param int $ttl Time to live in seconds (0 = forever)
    * @param array $tags Cache tags for group invalidation
    * @return bool Success status
    */
   public static function set(string $key, $value, int $ttl = self::DEFAULT_TTL, array $tags = []): bool
   {
      return self::getManager()->set($key, $value, $ttl, $tags);
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
   public static function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL, array $tags = [])
   {
      return self::getManager()->remember($key, $callback, $ttl, $tags);
   }

   /**
    * Remember forever
    * 
    * @param string $key Cache key
    * @param callable $callback Callback to execute if cache miss
    * @param array $tags Cache tags
    * @return mixed Cached or fresh value
    */
   public static function rememberForever(string $key, callable $callback, array $tags = [])
   {
      return self::getManager()->rememberForever($key, $callback, $tags);
   }

   /**
    * Check if cache key exists
    * 
    * @param string $key Cache key
    * @return bool Whether key exists
    */
   public static function has(string $key): bool
   {
      return self::getManager()->has($key);
   }

   /**
    * Delete cached value
    * 
    * @param string $key Cache key
    * @return bool Success status
    */
   public static function delete(string $key): bool
   {
      return self::getManager()->delete($key);
   }

   /**
    * Get multiple cached values
    * 
    * @param array $keys Array of cache keys
    * @return array Key-value pairs of cached data
    */
   public static function getMultiple(array $keys): array
   {
      return self::getManager()->getMultiple($keys);
   }

   /**
    * Store multiple values in cache
    * 
    * @param array $values Key-value pairs to cache
    * @param int $ttl Time to live in seconds
    * @param array $tags Cache tags
    * @return bool Success status
    */
   public static function setMultiple(array $values, int $ttl = self::DEFAULT_TTL, array $tags = []): bool
   {
      return self::getManager()->setMultiple($values, $ttl, $tags);
   }

   /**
    * Delete multiple cached values
    * 
    * @param array $keys Array of cache keys
    * @return bool Success status
    */
   public static function deleteMultiple(array $keys): bool
   {
      return self::getManager()->deleteMultiple($keys);
   }

   /**
    * Flush all cache entries
    * 
    * @return int Number of files deleted (for backward compatibility)
    */
   public static function flush(): int
   {
      $stats = self::getManager()->getStats();
      $totalEntries = 0;

      foreach ($stats['drivers'] as $driverStats) {
         $totalEntries += $driverStats['total_entries'] ?? 0;
      }

      self::getManager()->flush();
      return $totalEntries;
   }

   /**
    * Invalidate cache entries by tag
    * 
    * @param string $tag Tag name
    * @return int Number of entries invalidated
    */
   public static function invalidateTag(string $tag): int
   {
      return self::getManager()->invalidateTag($tag);
   }

   /**
    * Invalidate multiple tags
    * 
    * @param array $tags Array of tag names
    * @return int Total number of entries invalidated
    */
   public static function invalidateTags(array $tags): int
   {
      return self::getManager()->invalidateTags($tags);
   }

   /**
    * Clean up expired cache entries
    * 
    * @return int Number of expired entries deleted
    */
   public static function cleanup(): int
   {
      return self::getManager()->cleanup();
   }

   /**
    * Get cache statistics
    * 
    * @return array Cache stats (backward compatible format)
    */
   public static function stats(): array
   {
      $stats = self::getManager()->getStats();

      // Convert to backward compatible format
      $totalEntries = 0;
      $totalSize = 0;
      $expiredEntries = 0;

      foreach ($stats['drivers'] as $driverStats) {
         $totalEntries += $driverStats['total_entries'] ?? 0;
         $totalSize += $driverStats['total_size'] ?? 0;
         $expiredEntries += $driverStats['expired_entries'] ?? 0;
      }

      return [
         'total_entries' => $totalEntries,
         'total_size' => $totalSize,
         'total_size_mb' => round($totalSize / 1024 / 1024, 2),
         'expired_entries' => $expiredEntries,
         'drivers' => $stats['drivers'],
         'manager' => $stats['manager']
      ];
   }

   /**
    * Warm cache with predefined data
    * 
    * @param array $data Key-value pairs to cache
    * @param int $ttl Time to live
    * @param array $tags Cache tags
    * @return bool Success status
    */
   public static function warm(array $data, int $ttl = self::DEFAULT_TTL, array $tags = []): bool
   {
      return self::getManager()->warm($data, $ttl, $tags);
   }

   /**
    * Get cache driver instance
    * 
    * @param string $driver Driver name
    * @return CacheInterface Driver instance
    */
   public static function driver(string $driver = null): CacheInterface
   {
      return self::getManager()->driver($driver);
   }

   /**
    * Increment cached value
    * 
    * @param string $key Cache key
    * @param int $value Value to increment by
    * @return int|false New value or false on failure
    */
   public static function increment(string $key, int $value = 1)
   {
      $driver = self::getManager()->driver();
      if (method_exists($driver, 'increment')) {
         return $driver->increment($key, $value);
      }

      // Fallback implementation
      $current = self::get($key, 0);
      if (!is_numeric($current)) {
         return false;
      }

      $new = (int)$current + $value;
      return self::set($key, $new) ? $new : false;
   }

   /**
    * Decrement cached value
    * 
    * @param string $key Cache key
    * @param int $value Value to decrement by
    * @return int|false New value or false on failure
    */
   public static function decrement(string $key, int $value = 1)
   {
      return self::increment($key, -$value);
   }

   // Legacy method aliases for backward compatibility

   /**
    * @deprecated Use invalidateTag() instead
    */
   public static function forgetTag(string $tag): int
   {
      return self::invalidateTag($tag);
   }

   /**
    * @deprecated Use delete() instead
    */
   public static function forget(string $key): bool
   {
      return self::delete($key);
   }

   /**
    * @deprecated Use set() with TTL 0 instead
    */
   public static function forever(string $key, $value, array $tags = []): bool
   {
      return self::set($key, $value, 0, $tags);
   }

   /**
    * @deprecated Use flush() instead
    */
   public static function clear(): int
   {
      return self::flush();
   }
}
