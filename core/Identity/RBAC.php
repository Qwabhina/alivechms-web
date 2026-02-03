<?php

/**
 * Role-Based Access Control Service
 *
 * Directs permission logic and delegates data persistence to RBACRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.1.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Identity;

use AliveChMS\Core\System\Helpers;
use Exception;

class RBAC
{
   public static function hasPermission(int $userId, string $permission): bool
   {
      return in_array($permission, self::getUserPermissions($userId), true);
   }

   public static function getUserPermissions(int $userId): array
   {
      return (new RBACRepository())->getUserPermissions($userId);
   }

   public static function getUserRoles(int $userId): array
   {
      return (new RBACRepository())->getUserRoles($userId);
   }

   public static function assignRole(int $userId, int $roleId, int $assignedBy, ?string $start = null, ?string $end = null, ?string $notes = null): array
   {
      $repo = new RBACRepository();
      $role = $repo->findRoleById($roleId);
      if (!$role)
         throw new Exception('Role not found');

      $repo->assignRole([
         'MbrID' => $userId,
         'RoleID' => $roleId,
         'StartDate' => $start,
         'EndDate' => $end,
         'IsActive' => 1,
         'AssignedBy' => $assignedBy,
         'AssignedAt' => date('Y-m-d H:i:s'),
         'Notes' => $notes
      ]);

      PermissionAudit::log('role_assigned', $assignedBy, ['role' => $role['RoleName'], 'to' => $userId]);
      return ['status' => 'success'];
   }

   public static function removeRole(int $userId, int $roleId, int $removedBy): array
   {
      $repo = new RBACRepository();
      if ($repo->removeRole($userId, $roleId) === 0)
         throw new Exception('Role assignment not found');

      PermissionAudit::log('role_removed', $removedBy, ['role_id' => $roleId, 'from' => $userId]);
      return ['status' => 'success'];
   }
}
