<?php

/**
 * Milestone Type Management Service
 *
 * Orchestrates milestone categories and delegates to LookupRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\System\LookupRepository;
use AliveChMS\Core\System\Helpers;

class MilestoneType
{
   public static function getAll(): array
   {
      $repo = new LookupRepository('milestone_type', 'MilestoneTypeID');
      return ['data' => $repo->getAll()];
   }

   public static function create(array $data): array
   {
      $repo = new LookupRepository('milestone_type', 'MilestoneTypeID');
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $id = $repo->create([
         'TypeName' => trim($data['name']),
         'Description' => $data['description'] ?? null,
         'IsActive' => 1
      ]);

      return ['status' => 'success', 'type_id' => $id];
   }
}
