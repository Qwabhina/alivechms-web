<?php

/**
 * Pledge Type Management
 *
 * CRUD operations for pledge types (Building Fund, Missions, etc.)
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class PledgeType
{
   /**
    * Create a new pledge type
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'name' => 'required|max:100',
         'description' => 'max:500|nullable'
      ]);

      $name = trim($data['name']);

      if (!empty($orm->getWhere('pledge_type', ['PledgeTypeName' => $name, 'IsActive' => 1]))) {
         ResponseHelper::error('Pledge type name already exists', 400);
      }

      $typeId = $orm->insert('pledge_type', [
         'PledgeTypeName' => $name,
         'Description' => $data['description'] ?? null,
         'IsActive' => 1,
         'CreatedAt' => date('Y-m-d H:i:s')
      ])['id'];

      return ['status' => 'success', 'pledge_type_id' => $typeId];
   }

   /**
    * Update an existing pledge type
    */
   public static function update(int $typeId, array $data): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('pledge_type', ['PledgeTypeID' => $typeId, 'IsActive' => 1]);
      if (empty($type)) {
         ResponseHelper::error('Pledge type not found', 404);
      }

      $update = [];

      if (!empty($data['name'])) {
         $name = trim($data['name']);
         $existing = $orm->runQuery(
            "SELECT PledgeTypeID FROM pledge_type WHERE PledgeTypeName = :name AND PledgeTypeID != :id AND IsActive = 1",
            [':name' => $name, ':id' => $typeId]
         );
         if (!empty($existing)) {
            ResponseHelper::error('Pledge type name already exists', 400);
         }
         $update['PledgeTypeName'] = $name;
      }

      if (isset($data['description'])) {
         $update['Description'] = $data['description'] ?: null;
      }

      if (!empty($update)) {
         $orm->update('pledge_type', $update, ['PledgeTypeID' => $typeId]);
      }

      return ['status' => 'success', 'pledge_type_id' => $typeId];
   }

   /**
    * Delete a pledge type (only if unused)
    */
   public static function delete(int $typeId): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('pledge_type', ['PledgeTypeID' => $typeId, 'IsActive' => 1]);
      if (empty($type)) {
         ResponseHelper::error('Pledge type not found', 404);
      }

      if (!empty($orm->getWhere('pledge', ['PledgeTypeID' => $typeId]))) {
         ResponseHelper::error('Cannot delete pledge type that is in use', 400);
      }

      // Soft delete
      $orm->update('pledge_type', ['IsActive' => 0], ['PledgeTypeID' => $typeId]);
      return ['status' => 'success'];
   }

   /**
    * Get a single pledge type
    */
   public static function get(int $typeId): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('pledge_type', ['PledgeTypeID' => $typeId, 'IsActive' => 1]);
      if (empty($type)) {
         ResponseHelper::error('Pledge type not found', 404);
      }

      return $type[0];
   }

   /**
    * Get all pledge types
    */
   public static function getAll(): array
   {
      $orm = new ORM();
      $types = $orm->runQuery(
         "SELECT PledgeTypeID, PledgeTypeName, Description, IsActive, CreatedAt 
          FROM pledge_type 
          WHERE IsActive = 1
          ORDER BY PledgeTypeName ASC"
      );

      return ['data' => $types];
   }
}
