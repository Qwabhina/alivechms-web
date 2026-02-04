<?php

/**
 * Church Group Management Service
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\Operations\GroupRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use Exception;

class Group
{
   /**
    * Create a new church group
    */
   public static function create(array $data): array
   {
      $repo = new GroupRepository();

      Helpers::validateInput($data, [
         'name'        => 'required|max:100',
         'leader_id'   => 'required|numeric',
         'type_id'     => 'required|numeric',
         'description' => 'max:500|nullable',
      ]);

      $leaderId = (int)$data['leader_id'];
      $typeId   = (int)$data['type_id'];

      // Check duplicate name
      $groups = $repo->findAll(1, 0, ['name' => $data['name']]);
      if ($groups['total'] > 0) {
         ResponseHelper::error('Group name already exists', 400);
      }

      $repo->beginTransaction();
      try {
         $groupId = $repo->create([
            'GroupName'        => $data['name'],
            'GroupLeaderID'    => $leaderId,
            'GroupDescription' => $data['description'] ?? null,
            'GroupTypeID'      => $typeId,
            'CreatedAt'        => date('Y-m-d H:i:s')
         ]);

         // Auto-add leader as member
         $repo->addMember($groupId, $leaderId);

         $repo->commit();
         return ['status' => 'success', 'group_id' => $groupId];
      } catch (Exception $e) {
         $repo->rollBack();
         Helpers::logError("Group creation failed: " . $e->getMessage());
         throw $e;
      }
   }

   public static function update(int $groupId, array $data): array
   {
      $repo = new GroupRepository();
      $group = $repo->findById($groupId);

      if (!$group)
         ResponseHelper::error('Group not found', 404);

      $update = [];
      if (!empty($data['name']))
         $update['GroupName'] = $data['name'];
      if (!empty($data['leader_id']))
         $update['GroupLeaderID'] = (int) $data['leader_id'];
      if (isset($data['description']))
         $update['GroupDescription'] = $data['description'];

      if (!empty($update)) {
         $update['UpdatedAt'] = date('Y-m-d H:i:s');
         $repo->update($groupId, $update);
      }

      return ['status' => 'success'];
   }

   public static function addMember(int $groupId, int $memberId): array
   {
      $repo = new GroupRepository();
      if ($repo->isMember($groupId, $memberId)) {
         ResponseHelper::error('Member already in group', 400);
      }

      $repo->addMember($groupId, $memberId);
      return ['status' => 'success'];
   }

   public static function removeMember(int $groupId, int $memberId): array
   {
      $repo = new GroupRepository();
      $group = $repo->findById($groupId);

      if ($memberId === (int) $group['GroupLeaderID']) {
         ResponseHelper::error('Cannot remove group leader from membership', 400);
      }

      $repo->removeMember($groupId, $memberId);
      return ['status' => 'success'];
   }

   public static function get(int $groupId): array
   {
      $repo = new GroupRepository();
      $group = $repo->findById($groupId);
      if (!$group)
         ResponseHelper::error('Group not found', 404);
      return $group;
   }

   public static function getMembers(int $groupId, int $page = 1, int $limit = 10): array
   {
      $repo = new GroupRepository();
      $offset = ($page - 1) * $limit;
      $result = $repo->getMembers($groupId, $limit, $offset);

      return [
         'data' => $result['data'],
         'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
            'pages' => (int) ceil($result['total'] / $limit)
         ]
      ];
   }

   public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
   {
      $repo = new GroupRepository();
      $offset = ($page - 1) * $limit;
      $result = $repo->findAll($limit, $offset, $filters);

      return [
         'data' => $result['data'],
         'pagination' => [
            'page'  => $page,
            'limit' => $limit,
            'total' => $result['total'],
            'pages' => (int) ceil($result['total'] / $limit)
         ]
      ];
   }

   public static function delete(int $groupId): array
   {
      $repo = new GroupRepository();
      $repo->delete($groupId);
      return ['status' => 'success'];
   }
}