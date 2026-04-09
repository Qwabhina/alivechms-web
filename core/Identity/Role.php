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

   public static function assignToMember(int $roleId, array $memberIds): array
   {
      $repo = new RBACRepository();
      $repo->assignToMember($roleId, $memberIds);
      return ['status' => 'success'];
   }

   public static function getMembers(int $roleId): array
   {
      $repo = new RBACRepository();
      return $repo->getMembers($roleId);
   }

   public static function removeMember(int $roleId, int $memberId): array
   {
      $repo = new RBACRepository();
      $repo->removeMember($roleId, $memberId);
      return ['status' => 'success'];
   }

   public static function getRoles(int $memberId): array
   {
      $repo = new RBACRepository();
      return $repo->getRoles($memberId);
   }

   public static function updateRoleForMember(int $roleId, int $memberId): array
   {
      $repo = new RBACRepository();
      $repo->updateRoleForMember($roleId, $memberId);
      return ['status' => 'success'];
   }

   public static function getPermissions(int $roleId): array
   {
      $repo = new RBACRepository();
      return $repo->getPermissionsForRole($roleId);
   }

   public static function getRolePermissions(int $roleId): array
   {
      $repo = new RBACRepository();
      return $repo->getRolePermissions($roleId);
   }

   public static function getPermissionRoles(int $permissionId): array
   {
      $repo = new RBACRepository();
      return $repo->getPermissionRoles($permissionId);
   }

   public static function updatePermissionForRole(int $roleId, array $permissionIds): array
   {
      $repo = new RBACRepository();
      $repo->updatePermissionForRole($roleId, $permissionIds);
      return ['status' => 'success'];
   }

   public static function removePermissionFromRole(int $roleId): array
   {
      $repo = new RBACRepository();
      $repo->removePermissionFromRole($roleId);
      return ['status' => 'success'];
   }

   public static function updatePermission(int $permissionId, array $data): array
   {
      $repo = new RBACRepository();
      $repo->updatePermission($permissionId, $data);
      return ['status' => 'success'];
   }

   public static function deletePermission(int $permissionId): array
   {
      $repo = new RBACRepository();
      $repo->deletePermission($permissionId);
      return ['status' => 'success'];
   }

   public static function createPermission(array $data): array
   {
      $repo = new RBACRepository();
      $repo->createPermission($data);
      return ['status' => 'success'];
   }

   public static function assignPermissionToRole(int $roleId, array $permissionIds): array
   {
      $repo = new RBACRepository();
      $repo->assignPermissionToRole($roleId, $permissionIds);
      return ['status' => 'success'];
   }
}