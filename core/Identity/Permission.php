<?php

/**
 * Permission Management Service
 *
 * Orchestrates system permissions and delegates to RBACRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Identity;

use AliveChMS\Core\Identity\RBACRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;

class Permission
{
   public static function create(array $data): array
   {
      $repo = new RBACRepository();
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $name = trim($data['name']);
      if ($repo->findPermissionByName($name)) {
         ResponseHelper::error('Permission already exists', 400);
      }

      $id = $repo->createPermission(['PermissionName' => $name, 'IsActive' => 1]);
      return ['status' => 'success', 'permission_id' => $id];
   }

   public static function delete(int $id): array
   {
      $repo = new RBACRepository();
      if ($repo->isPermissionInUse($id)) {
         ResponseHelper::error('Permission is in use by one or more roles', 400);
      }
      if ($repo->deletePermission($id) === 0) {
         ResponseHelper::error('Permission not found', 404);
      }
      return ['status' => 'success'];
   }

   public static function getAll(): array
   {
      return ['data' => (new RBACRepository())->getAllPermissions()];
   }
}