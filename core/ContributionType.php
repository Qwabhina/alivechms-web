<?php

/**
 * Contribution Type Management
 *
 * CRUD operations for contribution types (Tithe, Offering, etc.)
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class ContributionType
{
   /**
    * Create a new contribution type
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'name' => 'required|max:100',
         'description' => 'max:500|nullable'
      ]);

      $name = trim($data['name']);

      // Check for duplicate name
      $existing = $orm->runQuery(
         "SELECT ContributionTypeID FROM contribution_type WHERE ContributionTypeName = :name",
         [':name' => $name]
      );
      if (!empty($existing)) {
         ResponseHelper::error('Contribution type name already exists', 400);
      }

      $typeId = $orm->insert('contribution_type', [
         'ContributionTypeName' => $name,
         'ContributionTypeDescription' => $data['description'] ?? null,
         'IsActive' => 1,
         'IsTaxDeductible' => $data['is_tax_deductible'] ?? 1
      ])['id'];

      return ['status' => 'success', 'contribution_type_id' => $typeId];
   }

   /**
    * Update an existing contribution type
    */
   public static function update(int $typeId, array $data): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('contribution_type', ['ContributionTypeID' => $typeId]);
      if (empty($type)) {
         ResponseHelper::error('Contribution type not found', 404);
      }

      $update = [];

      if (!empty($data['name'])) {
         $name = trim($data['name']);
         $existing = $orm->runQuery(
            "SELECT ContributionTypeID FROM contribution_type WHERE ContributionTypeName = :name AND ContributionTypeID != :id",
            [':name' => $name, ':id' => $typeId]
         );
         if (!empty($existing)) {
            ResponseHelper::error('Contribution type name already exists', 400);
         }
         $update['ContributionTypeName'] = $name;
      }

      if (isset($data['description'])) {
         $update['ContributionTypeDescription'] = $data['description'] ?: null;
      }

      if (isset($data['is_tax_deductible'])) {
         $update['IsTaxDeductible'] = (int)$data['is_tax_deductible'];
      }

      if (!empty($update)) {
         $orm->update('contribution_type', $update, ['ContributionTypeID' => $typeId]);
      }

      return ['status' => 'success', 'contribution_type_id' => $typeId];
   }

   /**
    * Delete a contribution type (only if unused)
    */
   public static function delete(int $typeId): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('contribution_type', ['ContributionTypeID' => $typeId]);
      if (empty($type)) {
         ResponseHelper::error('Contribution type not found', 404);
      }

      // Check if type is in use
      $inUse = $orm->runQuery(
         "SELECT COUNT(*) AS cnt FROM contribution WHERE ContributionTypeID = :id AND Deleted = 0",
         [':id' => $typeId]
      )[0]['cnt'];

      if ($inUse > 0) {
         ResponseHelper::error('Cannot delete contribution type that is in use', 400);
      }

      $orm->delete('contribution_type', ['ContributionTypeID' => $typeId]);
      return ['status' => 'success'];
   }

   /**
    * Get a single contribution type
    */
   public static function get(int $typeId): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('contribution_type', ['ContributionTypeID' => $typeId]);
      if (empty($type)) {
         ResponseHelper::error('Contribution type not found', 404);
      }

      return $type[0];
   }

   /**
    * Get all contribution types
    */
   public static function getAll(): array
   {
      $orm = new ORM();
      $types = $orm->runQuery(
         "SELECT ContributionTypeID, ContributionTypeName, ContributionTypeDescription, IsActive, IsTaxDeductible 
          FROM contribution_type 
          WHERE IsActive = 1
          ORDER BY ContributionTypeName ASC"
      );

      return ['data' => $types];
   }
}
