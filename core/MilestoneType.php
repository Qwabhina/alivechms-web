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
    * 
    * @param array $data Input data (name, description, active)
    * @return array Result with ID
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
         "SELECT MilestoneTypeID FROM milestone_type WHERE TypeName = :name",
         [':name' => $name]
      );
      if (!empty($existing)) {
         ResponseHelper::error('Milestone type name already exists', 400);
      }

      $typeId = $orm->insert('milestone_type', [
         'TypeName' => $name,
         'Description' => $data['description'] ?? null,
         'Icon' => $data['icon'] ?? 'trophy',
         'Color' => $data['color'] ?? 'primary',
         'IsActive' => isset($data['active']) ? ($data['active'] ? 1 : 0) : 1,
         'CreatedAt' => date('Y-m-d H:i:s')
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

      if (!empty($data['icon'])) {
         $update['Icon'] = $data['icon'];
      }

      if (!empty($data['color'])) {
         $update['Color'] = $data['color'];
      }

      if (isset($data['active'])) {
         $update['IsActive'] = $data['active'] ? 1 : 0;
      }

      if (!empty($update)) {
         $orm->update('milestone_type', $update, ['MilestoneTypeID' => $typeId]);
      }

      return ['status' => 'success', 'milestone_type_id' => $typeId];
   }

   /**
    * Delete a milestone type (only if unused, otherwise soft delete/deactivate)
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
         "SELECT COUNT(*) AS cnt FROM member_milestone WHERE MilestoneTypeID = :id AND Deleted = 0",
         [':id' => $typeId]
      )[0]['cnt'];

      if ($inUse > 0) {
         // Soft delete by deactivating if in use, or return error?
         // Spec says: "Soft delete or hard if no references."
         // If in use, we can't hard delete. So we deactivate.
         $orm->update('milestone_type', ['IsActive' => 0], ['MilestoneTypeID' => $typeId]);
         return ['status' => 'success', 'message' => 'Milestone type deactivated (in use)'];
      }

      // Hard delete if not in use
      $orm->delete('milestone_type', ['MilestoneTypeID' => $typeId]);
      return ['status' => 'success', 'message' => 'Milestone type deleted'];
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

      $t = $type[0];
      return [
         'id' => $t['MilestoneTypeID'],
         'name' => $t['TypeName'],
         'description' => $t['Description'],
         'icon' => $t['Icon'],
         'color' => $t['Color'],
         'active' => (bool)$t['IsActive'],
         'created_at' => $t['CreatedAt']
      ];
   }

   /**
    * Get all milestone types
    */
   public static function getAll(bool $activeOnly = false): array
   {
      $orm = new ORM();

      $where = $activeOnly ? "WHERE IsActive = 1" : "";
      $types = $orm->runQuery(
         "SELECT MilestoneTypeID, TypeName, Description, Icon, Color, IsActive, CreatedAt 
          FROM milestone_type $where ORDER BY TypeName ASC"
      );

      return array_map(function ($t) {
         return [
            'id' => $t['MilestoneTypeID'],
            'name' => $t['TypeName'],
            'description' => $t['Description'],
            'icon' => $t['Icon'],
            'color' => $t['Color'],
            'active' => (bool)$t['IsActive'],
            'created_at' => $t['CreatedAt']
         ];
      }, $types);
   }
}
