<?php

/**
 * Contribution Type Management
 *
 * Standardized lookup management for financial contribution categories.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\System\ORM;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;

class ContributionType
{
   public static function getAll(): array
   {
      $orm = new ORM();
      return ['data' => $orm->getWhere('contribution_type', ['IsActive' => 1])];
   }

   public static function create(array $data): array
   {
      $orm = new ORM();
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $typeId = $orm->insert('contribution_type', [
         'ContributionTypeName' => trim($data['name']),
         'ContributionTypeDescription' => $data['description'] ?? null,
         'IsActive' => 1
      ])['id'];

      return ['status' => 'success', 'contribution_type_id' => $typeId];
   }
}
