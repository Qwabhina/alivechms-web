<?php

/**
 * Group Type Management
 *
 * Orchestrates group categories and delegates to LookupRepository.
 *
 * @package AliveChMS\Core
 * @version 2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\System\LookupRepository;
use AliveChMS\Core\System\Helpers;

class GroupType
{
   public static function getAll(): array
   {
      $repo = new LookupRepository('group_type', 'GroupTypeID');
      return ['data' => $repo->getAll()];
   }

   public static function create(array $data): array
   {
      $repo = new LookupRepository('group_type', 'GroupTypeID');
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $id = $repo->create([
         'GroupTypeName' => trim($data['name']),
         'GroupTypeDescription' => $data['description'] ?? null,
         'IsActive' => 1
      ]);

      return ['status' => 'success', 'type_id' => $id];
   }
}