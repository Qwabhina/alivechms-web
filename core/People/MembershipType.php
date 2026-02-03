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

   public static function create(array $data): array
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
}