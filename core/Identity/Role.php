<?php

/**
 * Role Management Service
 *
 * Orchestrates church roles and delegates to RBACRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.1.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Identity;

use AliveChMS\Core\Identity\RBACRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use Exception;

class Role
{
   public static function create(array $data): array
   {
      $repo = new RBACRepository();
      Helpers::validateInput($data, [
         'name' => 'required|max:100',
         'description' => 'max:500|nullable'
      ]);

      $name = trim($data['name']);
      if ($repo->findRoleByName($name)) {
         ResponseHelper::error('Role name already exists', 400);
      }

      $id = $repo->createRole([
         'RoleName' => $name,
         'RoleDescription' => $data['description'] ?? null,
         'IsActive' => 1,
         'IsSystemRole' => 0
      ]);

      return ['status' => 'success', 'role_id' => $id];
   }

   public static function update(int $roleId, array $data): array
   {
      $repo = new RBACRepository();
      $role = $repo->findRoleById($roleId);
      if (!$role)
         ResponseHelper::error('Role not found', 404);

      $update = [];
      if (!empty($data['name'])) {
         $newName = trim($data['name']);
         $existing = $repo->findRoleByName($newName);
         if ($existing && (int) $existing['RoleID'] !== $roleId) {
            ResponseHelper::error('Role name already exists', 400);
         }
         $update['RoleName'] = $newName;
      }

      if (isset($data['description']))
         $update['RoleDescription'] = $data['description'];

      if (!empty($update))
         $repo->updateRole($roleId, $update);

      return ['status' => 'success', 'role_id' => $roleId];
   }

   public static function delete(int $roleId): array
   {
      $repo = new RBACRepository();
      if ($repo->deleteRole($roleId) === 0)
         ResponseHelper::error('Role not found', 404);
      return ['status' => 'success'];
   }

   public static function get(int $roleId): array
   {
      $repo = new RBACRepository();
      $role = $repo->findRoleById($roleId);
      if (!$role)
         ResponseHelper::error('Role not found', 404);

      $role['permissions'] = $repo->getRolePermissions($roleId);
      return $role;
   }

   public static function getAll(): array
   {
      return ['data' => (new RBACRepository())->getAllRoles()];
   }

   public static function assignPermissions(int $roleId, array $permissionIds): array
   {
      $repo = new RBACRepository();
      $repo->syncRolePermissions($roleId, $permissionIds);
      return ['status' => 'success'];
   }
}