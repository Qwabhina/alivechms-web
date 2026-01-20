<?php

/**
 * Group Type Management Class
 *
 * Handles CRUD operations for group categories (e.g., Youth, Choir, Ushering).
 * Simple lookup table with uniqueness enforcement.
 *
 * @package AliveChMS\Core
 * @version 1.0.0
 * @author  Benjamin Ebo Yankson
 * @since   2025-11-20
 */

declare(strict_types=1);

class GroupType
{
   /**
    * Create a new group type
    *
    * @param array $data Group type data (name required)
    * @return array Success response with type_id
    * @throws Exception On validation or database failure
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'name' => 'required|max:100',
      ]);

      $name = trim($data['name']);

      // Enforce uniqueness
      $existing = $orm->getWhere('group_type', ['GroupTypeName' => $name, 'IsActive' => 1]);
      if (!empty($existing)) {
         ResponseHelper::error('Group type name already exists', 400);
      }

      $typeId = $orm->insert('group_type', [
         'GroupTypeName' => $name,
         'GroupTypeDescription' => $data['description'] ?? null,
         'IsActive' => 1
      ])['id'];

      return ['status' => 'success', 'type_id' => $typeId];
   }

   /**
    * Update an existing group type
    *
    * @param int   $typeId Group type ID
    * @param array $data   Updated data
    * @return array Success response
    */
   public static function update(int $typeId, array $data): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('group_type', ['GroupTypeID' => $typeId, 'IsActive' => 1]);
      if (empty($type)) {
         ResponseHelper::error('Group type not found', 404);
      }

      if (empty($data['name'])) {
         return ['status' => 'success', 'type_id' => $typeId];
      }

      $name = trim($data['name']);
      Helpers::validateInput(['name' => $name], ['name' => 'required|max:100']);

      $existing = $orm->getWhere('group_type', [
         'GroupTypeName'   => $name,
         'GroupTypeID!='   => $typeId,
         'IsActive' => 1
      ]);
      if (!empty($existing)) {
         ResponseHelper::error('Group type name already exists', 400);
      }

      $update = ['GroupTypeName' => $name];
      if (isset($data['description'])) {
         $update['GroupTypeDescription'] = $data['description'];
      }

      $orm->update('group_type', $update, ['GroupTypeID' => $typeId]);

      return ['status' => 'success', 'type_id' => $typeId];
   }

   /**
    * Delete a group type (only if not used by any group)
    *
    * @param int $typeId Group type ID
    * @return array Success response
    */
   public static function delete(int $typeId): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('group_type', ['GroupTypeID' => $typeId, 'IsActive' => 1]);
      if (empty($type)) {
         ResponseHelper::error('Group type not found', 404);
      }

      $used = $orm->getWhere('churchgroup', ['GroupTypeID' => $typeId, 'Deleted' => 0]);
      if (!empty($used)) {
         ResponseHelper::error('Cannot delete group type in use', 400);
      }

      // Soft delete
      $orm->update('group_type', ['IsActive' => 0], ['GroupTypeID' => $typeId]);

      return ['status' => 'success'];
   }

   /**
    * Retrieve a single group type
    *
    * @param int $typeId Group type ID
    * @return array Group type data
    */
   public static function get(int $typeId): array
   {
      $orm = new ORM();

      $type = $orm->getWhere('group_type', ['GroupTypeID' => $typeId, 'IsActive' => 1]);
      if (empty($type)) {
         ResponseHelper::error('Group type not found', 404);
      }

      return $type[0];
   }

   /**
    * Retrieve all group types (paginated)
    *
    * @param int $page  Page number
    * @param int $limit Items per page
    * @return array Paginated result
    */
   public static function getAll(int $page = 1, int $limit = 50): array
   {
      $orm = new ORM();
      $offset = ($page - 1) * $limit;

      $types = $orm->runQuery(
         "SELECT GroupTypeID, GroupTypeName, GroupTypeDescription, IsActive 
          FROM group_type 
          WHERE IsActive = 1
          ORDER BY GroupTypeName ASC 
          LIMIT :limit OFFSET :offset",
         [':limit' => $limit, ':offset' => $offset]
      );

      $total = $orm->runQuery('SELECT COUNT(*) AS total FROM group_type WHERE IsActive = 1')[0]['total'];

      return [
         'data' => $types,
            'pagination' => [
            'page'  => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => (int)ceil($total / $limit)
            ]
      ];
   }
}