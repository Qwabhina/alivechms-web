<?php

/**
 * Permission Cache Manager
 *
 * Provides high-performance permission caching to avoid repeated database queries.
 * Implements cache invalidation on permission changes.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 */

declare(strict_types=1);

class PermissionCache
{
   private const CACHE_TTL = 3600; // 1 hour
   private const CACHE_PREFIX = 'user_permissions_';

   /**
    * Get user permissions from cache or database
    *
    * @param int $userId User ID
    * @return array Array of permission names
    */
   public static function getUserPermissions(int $userId): array
   {
      $cacheKey = self::CACHE_PREFIX . $userId;

      // Try cache first
      $cached = Cache::get($cacheKey);
      if ($cached !== null) {
         return $cached;
      }

      // Fetch from database
      $permissions = self::fetchUserPermissionsFromDB($userId);

      // Store in cache
      Cache::set($cacheKey, $permissions, self::CACHE_TTL);

      return $permissions;
   }

   /**
    * Fetch user permissions from database
    *
    * @param int $userId User ID
    * @return array Array of permission names
    */
   private static function fetchUserPermissionsFromDB(int $userId): array
   {
      $orm = new ORM();

      $results = $orm->selectWithJoin(
         baseTable: 'memberrole mr',
         joins: [
            ['table' => 'churchrole cr',       'on' => 'mr.ChurchRoleID = cr.RoleID'],
            ['table' => 'rolepermission rp',   'on' => 'cr.RoleID = rp.ChurchRoleID'],
            ['table' => 'permission p',        'on' => 'rp.PermissionID = p.PermissionID']
         ],
         fields: ['p.PermissionName'],
         conditions: ['mr.MbrID' => ':user_id'],
         params: [':user_id' => $userId]
      );

      // Return unique permission names
      return array_values(array_unique(array_column($results, 'PermissionName')));
   }

   /**
    * Invalidate cache for a specific user
    *
    * @param int $userId User ID
    * @return void
    */
   public static function invalidateUser(int $userId): void
   {
      $cacheKey = self::CACHE_PREFIX . $userId;
      Cache::delete($cacheKey);
      Helpers::logError("[PermissionCache] Invalidated cache for user $userId");
   }

   /**
    * Invalidate cache for all users with a specific role
    *
    * @param int $roleId Role ID
    * @return void
    */
   public static function invalidateRole(int $roleId): void
   {
      $orm = new ORM();

      // Get all users with this role
      $users = $orm->runQuery(
         "SELECT DISTINCT MbrID FROM memberrole WHERE ChurchRoleID = :role_id",
         [':role_id' => $roleId]
      );

      foreach ($users as $user) {
         self::invalidateUser((int)$user['MbrID']);
      }

      Helpers::logError("[PermissionCache] Invalidated cache for role $roleId (" . count($users) . " users)");
   }

   /**
    * Invalidate cache for all users (use sparingly)
    *
    * @return void
    */
   public static function invalidateAll(): void
   {
      // This is expensive - only use when absolutely necessary
      // In production, implement a more efficient cache clearing strategy
      Cache::flush();
      Helpers::logError("[PermissionCache] Invalidated ALL permission caches");
   }

   /**
    * Warm up cache for a user
    *
    * @param int $userId User ID
    * @return array Permissions
    */
   public static function warmUp(int $userId): array
   {
      self::invalidateUser($userId);
      return self::getUserPermissions($userId);
   }
}
