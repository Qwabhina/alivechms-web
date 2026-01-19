<?php

/**
 * Comprehensive RBAC System
 *
 * Complete Role-Based Access Control implementation with:
 * - Role hierarchy and inheritance
 * - Permission caching with automatic invalidation
 * - Temporal role assignments
 * - Comprehensive audit logging
 * - High performance with database-level caching
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

class RBAC
{
   /**
    * Check if user has permission (with caching and inheritance)
    *
    * @param int $userId User ID
    * @param string $permission Permission name
    * @return bool Has permission
    */
   public static function hasPermission(int $userId, string $permission): bool
   {
      $permissions = self::getUserPermissions($userId);
      return in_array($permission, $permissions, true);
   }

   /**
    * Check if user has any of the permissions
    *
    * @param int $userId User ID
    * @param array $permissions Array of permission names
    * @return bool Has any permission
    */
   public static function hasAnyPermission(int $userId, array $permissions): bool
   {
      $userPermissions = self::getUserPermissions($userId);
      return !empty(array_intersect($permissions, $userPermissions));
   }

   /**
    * Check if user has all permissions
    *
    * @param int $userId User ID
    * @param array $permissions Array of permission names
    * @return bool Has all permissions
    */
   public static function hasAllPermissions(int $userId, array $permissions): bool
   {
      $userPermissions = self::getUserPermissions($userId);
      return empty(array_diff($permissions, $userPermissions));
   }

   /**
    * Get user permissions (uses stored procedure with caching)
    *
    * @param int $userId User ID
    * @return array Array of permission names
    */
   public static function getUserPermissions(int $userId): array
   {
      $db = Database::getInstance()->getConnection();

      try {
         $stmt = $db->prepare("CALL sp_get_user_permissions(:user_id)");
         $stmt->execute([':user_id' => $userId]);

         $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
         $stmt->closeCursor();

         return $permissions ?: [];
      } catch (Exception $e) {
         Helpers::logError("[RBAC] Failed to get user permissions: " . $e->getMessage());
         return [];
      }
   }

   /**
    * Get user permissions with details (role source, inheritance info)
    *
    * @param int $userId User ID
    * @return array Array of permission details
    */
   public static function getUserPermissionsDetailed(int $userId): array
   {
      $orm = new ORM();

      return $orm->runQuery("
            SELECT DISTINCT
                p.PermissionID,
                p.PermissionName,
                p.PermissionDescription,
                pc.CategoryName as PermissionCategory,
                cr.RoleID,
                cr.RoleName,
                CASE WHEN rh.ParentRoleID IS NOT NULL THEN 1 ELSE 0 END as IsInherited,
                CASE WHEN rh.ParentRoleID IS NOT NULL THEN parent_role.RoleName ELSE NULL END as InheritedFrom
            FROM memberrole mr
            JOIN churchrole cr ON mr.ChurchRoleID = cr.RoleID
            LEFT JOIN role_hierarchy rh ON cr.RoleID = rh.ChildRoleID
            LEFT JOIN churchrole parent_role ON rh.ParentRoleID = parent_role.RoleID
            JOIN rolepermission rp ON (rp.ChurchRoleID = cr.RoleID OR rp.ChurchRoleID = parent_role.RoleID)
            JOIN permission p ON rp.PermissionID = p.PermissionID
            LEFT JOIN permission_category pc ON p.CategoryID = pc.CategoryID
            WHERE mr.MbrID = :user_id
            AND mr.IsActive = 1
            AND (mr.StartDate IS NULL OR mr.StartDate <= CURDATE())
            AND (mr.EndDate IS NULL OR mr.EndDate >= CURDATE())
            AND cr.IsActive = 1
            AND p.IsActive = 1
            ORDER BY pc.CategoryName, p.PermissionName
        ", [':user_id' => $userId]);
   }

   /**
    * Get user roles (active only)
    *
    * @param int $userId User ID
    * @return array Array of roles
    */
   public static function getUserRoles(int $userId): array
   {
      $orm = new ORM();

      return $orm->runQuery("
            SELECT 
                cr.RoleID,
                cr.RoleName,
                cr.Description,
                mr.StartDate,
                mr.EndDate,
                mr.AssignedAt,
                CASE 
                    WHEN mr.EndDate IS NOT NULL AND mr.EndDate < CURDATE() THEN 'Expired'
                    WHEN mr.StartDate IS NOT NULL AND mr.StartDate > CURDATE() THEN 'Pending'
                    ELSE 'Active'
                END as Status
            FROM memberrole mr
            JOIN churchrole cr ON mr.ChurchRoleID = cr.RoleID
            WHERE mr.MbrID = :user_id
            AND mr.IsActive = 1
            AND cr.IsActive = 1
            ORDER BY cr.DisplayOrder
        ", [':user_id' => $userId]);
   }

   /**
    * Assign role to user
    *
    * @param int $userId User ID
    * @param int $roleId Role ID
    * @param int $assignedBy Who is assigning the role
    * @param string|null $startDate When role becomes active (YYYY-MM-DD)
    * @param string|null $endDate When role expires (YYYY-MM-DD)
    * @param string|null $notes Reason for assignment
    * @return array Result
    */
   public static function assignRole(
      int $userId,
      int $roleId,
      int $assignedBy,
      ?string $startDate = null,
      ?string $endDate = null,
      ?string $notes = null
   ): array {
      $orm = new ORM();

      // Validate user exists
      $user = $orm->getWhere('churchmember', ['MbrID' => $userId, 'Deleted' => 0]);
      if (empty($user)) {
         throw new Exception('User not found');
      }

      // Validate role exists
      $role = $orm->getWhere('churchrole', ['RoleID' => $roleId, 'IsActive' => 1]);
      if (empty($role)) {
         throw new Exception('Role not found or inactive');
      }

      // Check if already assigned
      $existing = $orm->runQuery("
            SELECT MemberRoleID FROM memberrole 
            WHERE MbrID = :user_id 
            AND ChurchRoleID = :role_id 
            AND IsActive = 1
            AND (EndDate IS NULL OR EndDate >= CURDATE())
        ", [':user_id' => $userId, ':role_id' => $roleId]);

      if (!empty($existing)) {
         throw new Exception('User already has this role assigned');
      }

      // Insert role assignment
      $memberRoleId = $orm->insert('memberrole', [
         'MbrID' => $userId,
         'ChurchRoleID' => $roleId,
         'StartDate' => $startDate,
         'EndDate' => $endDate,
         'IsActive' => 1,
         'AssignedBy' => $assignedBy,
         'Notes' => $notes
      ])['id'];

      // Audit log
      try {
         PermissionAudit::log('role_assigned_to_member', $assignedBy, [
            'role_id' => $roleId,
            'member_id' => $userId,
            'new_value' => [
               'role_name' => $role[0]['RoleName'],
               'start_date' => $startDate,
               'end_date' => $endDate,
               'notes' => $notes
            ]
         ]);
      } catch (Exception $e) {
         // Don't fail if audit logging fails
      }

      Helpers::logError("[RBAC] Role {$roleId} assigned to user {$userId} by {$assignedBy}");

      return [
         'status' => 'success',
         'member_role_id' => $memberRoleId
      ];
   }

   /**
    * Remove role from user
    *
    * @param int $userId User ID
    * @param int $roleId Role ID
    * @param int $removedBy Who is removing the role
    * @return array Result
    */
   public static function removeRole(int $userId, int $roleId, int $removedBy): array
   {
      $orm = new ORM();

      // Get role assignment
      $assignment = $orm->runQuery("
            SELECT mr.*, cr.RoleName 
            FROM memberrole mr
            JOIN churchrole cr ON mr.ChurchRoleID = cr.RoleID
            WHERE mr.MbrID = :user_id 
            AND mr.ChurchRoleID = :role_id 
            AND mr.IsActive = 1
        ", [':user_id' => $userId, ':role_id' => $roleId]);

      if (empty($assignment)) {
         throw new Exception('Role assignment not found');
      }

      // Deactivate (don't delete for audit trail)
      $orm->update('memberrole', [
         'IsActive' => 0,
         'EndDate' => date('Y-m-d')
      ], [
         'MbrID' => $userId,
         'ChurchRoleID' => $roleId
      ]);

      // Audit log
      try {
         PermissionAudit::log('role_removed_from_member', $removedBy, [
            'role_id' => $roleId,
            'member_id' => $userId,
            'old_value' => $assignment[0]
         ]);
      } catch (Exception $e) {
         // Don't fail if audit logging fails
      }

      Helpers::logError("[RBAC] Role {$roleId} removed from user {$userId} by {$removedBy}");

      return ['status' => 'success'];
   }

   /**
    * Get role hierarchy (parent-child relationships)
    *
    * @param int|null $roleId Get hierarchy for specific role, or all if null
    * @return array Hierarchy data
    */
   public static function getRoleHierarchy(?int $roleId = null): array
   {
      $orm = new ORM();

      $query = "
            SELECT 
                c.RoleID as ChildRoleID,
                c.RoleName as ChildRoleName,
                p.RoleID as ParentRoleID,
                p.RoleName as ParentRoleName,
                rh.InheritanceLevel
            FROM role_hierarchy rh
            JOIN churchrole c ON rh.ChildRoleID = c.RoleID
            JOIN churchrole p ON rh.ParentRoleID = p.RoleID
            WHERE c.IsActive = 1 AND p.IsActive = 1
        ";

      $params = [];
      if ($roleId !== null) {
         $query .= " AND (c.RoleID = :role_id OR p.RoleID = :role_id)";
         $params[':role_id'] = $roleId;
      }

      $query .= " ORDER BY rh.InheritanceLevel, c.DisplayOrder";

      return $orm->runQuery($query, $params);
   }

   /**
    * Get all permissions grouped by category
    *
    * @return array Permissions grouped by category
    */
   public static function getAllPermissionsGrouped(): array
   {
      $orm = new ORM();

      $permissions = $orm->runQuery("
            SELECT 
                pc.CategoryID,
                pc.CategoryName,
                pc.CategoryDescription,
                p.PermissionID,
                p.PermissionName,
                p.PermissionDescription,
                p.IsActive
            FROM permission p
            LEFT JOIN permission_category pc ON p.CategoryID = pc.CategoryID
            ORDER BY pc.DisplayOrder, pc.CategoryName, p.PermissionName
        ", []);

      // Group by category
      $grouped = [];
      foreach ($permissions as $perm) {
         $category = $perm['CategoryName'] ?? 'Uncategorized';
         if (!isset($grouped[$category])) {
            $grouped[$category] = [
               'category_id' => $perm['CategoryID'],
               'category_name' => $category,
               'category_description' => $perm['CategoryDescription'],
               'permissions' => []
            ];
         }
         $grouped[$category]['permissions'][] = [
            'permission_id' => $perm['PermissionID'],
            'permission_name' => $perm['PermissionName'],
            'permission_description' => $perm['PermissionDescription'],
            'is_active' => $perm['IsActive']
         ];
      }

      return array_values($grouped);
   }

   /**
    * Get role with all permissions (direct + inherited)
    *
    * @param int $roleId Role ID
    * @return array Role data with permissions
    */
   public static function getRoleWithPermissions(int $roleId): array
   {
      $orm = new ORM();

      // Get role details
      $role = $orm->getWhere('churchrole', ['RoleID' => $roleId]);
      if (empty($role)) {
         throw new Exception('Role not found');
      }

      $roleData = $role[0];

      // Get direct permissions
      $directPerms = $orm->runQuery("
            SELECT p.PermissionID, p.PermissionName, p.PermissionDescription
            FROM rolepermission rp
            JOIN permission p ON rp.PermissionID = p.PermissionID
            WHERE rp.ChurchRoleID = :role_id
            AND p.IsActive = 1
            ORDER BY p.PermissionName
        ", [':role_id' => $roleId]);

      // Get inherited permissions
      $inheritedPerms = $orm->runQuery("
            SELECT DISTINCT
                p.PermissionID, 
                p.PermissionName, 
                p.PermissionDescription,
                parent_role.RoleName as InheritedFrom
            FROM role_hierarchy rh
            JOIN rolepermission rp ON rh.ParentRoleID = rp.ChurchRoleID
            JOIN permission p ON rp.PermissionID = p.PermissionID
            JOIN churchrole parent_role ON rh.ParentRoleID = parent_role.RoleID
            WHERE rh.ChildRoleID = :role_id
            AND p.IsActive = 1
            ORDER BY p.PermissionName
        ", [':role_id' => $roleId]);

      $roleData['direct_permissions'] = $directPerms;
      $roleData['inherited_permissions'] = $inheritedPerms;
      $roleData['total_permissions'] = count($directPerms) + count($inheritedPerms);

      return $roleData;
   }

   /**
    * Invalidate permission cache for user
    *
    * @param int $userId User ID
    * @return void
    */
   public static function invalidateUserCache(int $userId): void
   {
      $db = Database::getInstance()->getConnection();

      try {
         $stmt = $db->prepare("CALL sp_invalidate_permission_cache(:user_id)");
         $stmt->execute([':user_id' => $userId]);
         $stmt->closeCursor();

         Helpers::logError("[RBAC] Cache invalidated for user {$userId}");
      } catch (Exception $e) {
         Helpers::logError("[RBAC] Failed to invalidate cache: " . $e->getMessage());
      }
   }

   /**
    * Invalidate permission cache for all users with a role
    *
    * @param int $roleId Role ID
    * @return void
    */
   public static function invalidateRoleCache(int $roleId): void
   {
      $db = Database::getInstance()->getConnection();

      try {
         $stmt = $db->prepare("CALL sp_invalidate_role_cache(:role_id)");
         $stmt->execute([':role_id' => $roleId]);
         $stmt->closeCursor();

         Helpers::logError("[RBAC] Cache invalidated for role {$roleId}");
      } catch (Exception $e) {
         Helpers::logError("[RBAC] Failed to invalidate role cache: " . $e->getMessage());
      }
   }

   /**
    * Check if user has role
    *
    * @param int $userId User ID
    * @param string $roleName Role name
    * @return bool Has role
    */
   public static function hasRole(int $userId, string $roleName): bool
   {
      $roles = self::getUserRoles($userId);
      foreach ($roles as $role) {
         if ($role['RoleName'] === $roleName && $role['Status'] === 'Active') {
            return true;
         }
      }
      return false;
   }

   /**
    * Check if user is Super Admin
    *
    * @param int $userId User ID
    * @return bool Is Super Admin
    */
   public static function isSuperAdmin(int $userId): bool
   {
      return self::hasRole($userId, 'Super Admin');
   }

   /**
    * Get permission categories
    *
    * @return array Categories
    */
   public static function getPermissionCategories(): array
   {
      $orm = new ORM();
      return $orm->runQuery("
            SELECT * FROM permission_category 
            ORDER BY DisplayOrder, CategoryName
        ", []);
   }
}
