<?php

/**
 * Milestone Type Management
 *
 * CRUD operations for milestone types (Baptism, Marriage, Salvation, etc.)
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class MilestoneType
{
   /**
    * Create a new milestone type
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'name' => 'required|max:100',
         'description' => 'max:500|nullable',
         'icon' => 'max:50|nullable',
         'color' => 'max:20|nullable'
      ]);

      $name = trim($data['name']);

      // Check for duplicate name
      $existing = $orm->runQuery(
         "SELECT MilestoneTypeID FROM milestone_type WHERE TypeName = :name",
         [':name' => $name]
      );
      if (!empty($existing)) {
         ResponseHelper::error('Milestone type name already exists', 400);
      }

      $typeId = $orm->insert('milestone_type', [
         'TypeName' => $name,
         'Description' => $data['description'] ?? null,
         'IsActive' => 1
      ])['id'];

      return ['status' => 'success', 'milestone_type_id' => $typeId];
   }

   /**
    * Update an existing milestone type
    */
   public static function update(int $typeId, array $data): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('milestone_type', ['MilestoneTypeID' => $typeId]);
      if (empty($type)) {
         ResponseHelper::error('Milestone type not found', 404);
      }

      $update = [];

      if (!empty($data['name'])) {
         $name = trim($data['name']);
         $existing = $orm->runQuery(
            "SELECT MilestoneTypeID FROM milestone_type WHERE TypeName = :name AND MilestoneTypeID != :id",
            [':name' => $name, ':id' => $typeId]
         );
         if (!empty($existing)) {
            ResponseHelper::error('Milestone type name already exists', 400);
         }
         $update['TypeName'] = $name;
      }

      if (isset($data['description'])) {
         $update['Description'] = $data['description'] ?: null;
      }

      if (isset($data['is_active'])) {
         $update['IsActive'] = $data['is_active'] ? 1 : 0;
      }

      if (!empty($update)) {
         $orm->update('milestone_type', $update, ['MilestoneTypeID' => $typeId]);
      }

      return ['status' => 'success', 'milestone_type_id' => $typeId];
   }

   /**
    * Delete a milestone type (only if unused)
    */
   public static function delete(int $typeId): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('milestone_type', ['MilestoneTypeID' => $typeId]);
      if (empty($type)) {
         ResponseHelper::error('Milestone type not found', 404);
      }

      // Check if type is in use
      $inUse = $orm->runQuery(
         "SELECT COUNT(*) AS cnt FROM member_milestone WHERE MilestoneTypeID = :id",
         [':id' => $typeId]
      )[0]['cnt'];

      if ($inUse > 0) {
         ResponseHelper::error('Cannot delete milestone type that is in use', 400);
      }

      $orm->delete('milestone_type', ['MilestoneTypeID' => $typeId]);
      return ['status' => 'success'];
   }

   /**
    * Get a single milestone type
    */
   public static function get(int $typeId): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('milestone_type', ['MilestoneTypeID' => $typeId]);
      if (empty($type)) {
         ResponseHelper::error('Milestone type not found', 404);
      }

      return $type[0];
   }

   /**
    * Get all milestone types
    */
   public static function getAll(bool $activeOnly = false): array
   {
      $orm = new ORM();

      $where = $activeOnly ? "WHERE IsActive = 1" : "";
      $types = $orm->runQuery(
         "SELECT MilestoneTypeID, TypeName, Description, IsActive, CreatedAt 
          FROM milestone_type $where ORDER BY TypeName ASC"
      );

      return ['data' => $types];
   }
}
