<?php

/**
 * Pledge Type Management Service
 *
 * Orchestrates pledge categories and delegates to LookupRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\System\LookupRepository;
use AliveChMS\Core\System\Helpers;

class PledgeType
{
   public static function getAll(): array
   {
      $repo = new LookupRepository('pledge_type', 'PledgeTypeID');
      return ['data' => $repo->getAll()];
   }

   public static function create(array $data): array
   {
      $repo = new LookupRepository('pledge_type', 'PledgeTypeID');
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $id = $repo->create([
         'PledgeTypeName' => trim($data['name']),
         'Description' => $data['description'] ?? null,
         'IsActive' => 1,
         'CreatedAt' => date('Y-m-d H:i:s')
      ]);

      return ['status' => 'success', 'type_id' => $id];
   }

   public static function update(int $id, array $data): array
   {
      $repo = new LookupRepository('pledge_type', 'PledgeTypeID');
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $repo->update($id, [
         'PledgeTypeName' => trim($data['name']),
         'Description' => $data['description'] ?? null,
         'UpdatedAt' => date('Y-m-d H:i:s')
      ]);

      return ['status' => 'success', 'type_id' => $id];
   }

   public static function delete(int $id): array
   {
      $repo = new LookupRepository('pledge_type', 'PledgeTypeID');
      $repo->update($id, [
         'Deleted' => 1,
         'DeletedAt' => date('Y-m-d H:i:s')
      ]);

      return ['status' => 'success', 'type_id' => $id];
   }
}
