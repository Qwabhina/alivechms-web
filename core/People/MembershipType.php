<?php

/**
 * Membership Type Management
 *
 * Orchestrates membership categories and delegates to LookupRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\System\LookupRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;

class MembershipType
{
   public static function getAll(): array
   {
      $repo = new LookupRepository('membership_type', 'MshipTypeID');
      return ['data' => $repo->getAll()];
   }

   public static function create(int $branchId, array $data): array
   {
      $repo = new LookupRepository('membership_type', 'MshipTypeID');
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $id = $repo->create([
         'MshipTypeName' => trim($data['name']),
         'MshipTypeDescription' => $data['description'] ?? null,
         'IsActive' => 1
      ]);

      return ['status' => 'success', 'type_id' => $id];
   }

   public static function update(int $id, array $data): array
   {
      $repo = new LookupRepository('membership_type', 'MshipTypeID');
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $id = $repo->update($id, [
         'MshipTypeName' => trim($data['name']),
         'MshipTypeDescription' => $data['description'] ?? null,
         'IsActive' => 1
      ]);

      return ['status' => 'success', 'type_id' => $id];
   }

   public static function delete(int $id): array
   {
      $repo = new LookupRepository('membership_type', 'MshipTypeID');
      $id = $repo->delete($id);

      return ['status' => 'success', 'type_id' => $id];
   }

   public static function getById(int $branchId, int $id): array
   {
      $repo = new LookupRepository('membership_type', 'MshipTypeID');
      return ['data' => $repo->getById($branchId, $id)];
   }

   public static function getByBranchId(int $branchId): array
   {
      $repo = new LookupRepository('membership_type', 'MshipTypeID');
      return ['data' => $repo->getByBranchId($branchId)];
   }

   public static function getByBranchIdAndId(int $branchId, int $id): array
   {
      $repo = new LookupRepository('membership_type', 'MshipTypeID');
      return ['data' => $repo->getByBranchIdAndId($branchId, $id)];
   }
}