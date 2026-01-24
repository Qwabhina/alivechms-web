<?php

/**
 * Church Group Management
 *
 * Handles creation, update, deletion, membership management,
 * and retrieval of church groups (choir, youth, ushering, etc.).
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class Group
{
   /**
    * Create a new church group
    *
    * @param array $data Group creation payload
    * @return array ['status' => 'success', 'group_id' => int]
    * @throws Exception On validation or database failure
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'name'        => 'required|max:100',
         'leader_id'   => 'required|numeric',
         'type_id'     => 'required|numeric',
         'description' => 'max:500|nullable',
      ]);

      $leaderId = (int)$data['leader_id'];
      $typeId   = (int)$data['type_id'];

      // Validate leader - check membership status via lookup table
      $leader = $orm->selectWithJoin(
         baseTable: 'churchmember m',
         joins: [['table' => 'membership_status ms', 'on' => 'm.MbrMembershipStatusID = ms.StatusID']],
         fields: ['m.MbrID', 'ms.StatusName'],
         conditions: ['m.MbrID' => ':leader_id', 'm.Deleted' => 0],
         params: [':leader_id' => $leaderId]
      );

      if (empty($leader) || $leader[0]['StatusName'] !== 'Active') {
         ResponseHelper::error('Invalid or inactive group leader', 400);
      }

      // Validate group type
      if (empty($orm->getWhere('group_type', ['GroupTypeID' => $typeId, 'IsActive' => 1]))) {
         ResponseHelper::error('Invalid group type', 400);
      }

      // Check duplicate name
      if (!empty($orm->getWhere('church_group', ['GroupName' => $data['name']]))) {
         ResponseHelper::error('Group name already exists', 400);
      }

      $orm->beginTransaction();
      try {
         $groupId = $orm->insert('church_group', [
            'GroupName'        => $data['name'],
            'GroupLeaderID'    => $leaderId,
            'GroupDescription' => $data['description'] ?? null,
            'GroupTypeID'      => $typeId,
            'CreatedAt'        => date('Y-m-d H:i:s')
         ])['id'];

         // Auto-add leader as member
         $orm->insert('group_member', [
            'GroupID'  => $groupId,
            'MbrID'    => $leaderId,
            'JoinedAt' => date('Y-m-d H:i:s')
         ]);

         $orm->commit();
         Helpers::logError("New group created: GroupID $groupId – {$data['name']}");
         return ['status' => 'success', 'group_id' => $groupId];
      } catch (Exception $e) {
            $orm->rollBack();
         Helpers::logError("Group creation failed: " . $e->getMessage());
         throw $e;
      }
   }

   /**
    * Update group details
    *
    * @param int   $groupId Group ID
    * @param array $data    Updated data
    * @return array Success response
    */
   public static function update(int $groupId, array $data): array
   {
      $orm = new ORM();

      $group = $orm->getWhere('church_group', ['GroupID' => $groupId]);
      if (empty($group)) {
         ResponseHelper::error('Group not found', 404);
      }

      $update = [];

      if (!empty($data['name'])) {
         if (!empty($orm->getWhere('church_group', ['GroupName' => $data['name'], 'GroupID!=' => $groupId]))) {
            ResponseHelper::error('Group name already exists', 400);
         }
         $update['GroupName'] = $data['name'];
      }

      if (!empty($data['leader_id'])) {
         $leader = $orm->selectWithJoin(
            baseTable: 'churchmember m',
            joins: [['table' => 'membership_status ms', 'on' => 'm.MbrMembershipStatusID = ms.StatusID']],
            fields: ['m.MbrID', 'ms.StatusName'],
            conditions: ['m.MbrID' => ':leader_id', 'm.Deleted' => 0],
            params: [':leader_id' => (int)$data['leader_id']]
         );

         if (empty($leader) || $leader[0]['StatusName'] !== 'Active') {
            ResponseHelper::error('Invalid or inactive leader', 400);
         }
         $update['GroupLeaderID'] = (int)$data['leader_id'];
      }

      if (!empty($data['type_id'])) {
         if (empty($orm->getWhere('group_type', ['GroupTypeID' => (int)$data['type_id'], 'IsActive' => 1]))) {
            ResponseHelper::error('Invalid group type', 400);
         }
         $update['GroupTypeID'] = (int)$data['type_id'];
      }

      if (isset($data['description'])) {
         $update['GroupDescription'] = $data['description'];
      }

      if (!empty($update)) {
         $update['UpdatedAt'] = date('Y-m-d H:i:s');
         if (!empty($_SESSION['user_id'])) {
            $update['UpdatedBy'] = (int)$_SESSION['user_id'];
         }
         $orm->update('church_group', $update, ['GroupID' => $groupId]);
      }

      return ['status' => 'success', 'group_id' => $groupId];
   }

   /**
    * Delete group (only if no members or messages)
    *
    * @param int $groupId Group ID
    * @return array ['status' => 'success']
    */
   public static function delete(int $groupId): array
   {
      $orm = new ORM();

      $group = $orm->getWhere('church_group', ['GroupID' => $groupId]);
      if (empty($group)) {
         ResponseHelper::error('Group not found', 404);
      }

      $members   = $orm->getWhere('group_member', ['GroupID' => $groupId]);
      $messages  = $orm->getWhere('communication', ['TargetGroupID' => $groupId]);

      if (!empty($members) || !empty($messages)) {
         ResponseHelper::error('Cannot delete group with members or messages', 400);
      }

      // Soft delete with audit trail
      $deleteData = [
         'Deleted' => 1,
         'DeletedAt' => date('Y-m-d H:i:s')
      ];

      if (!empty($_SESSION['user_id'])) {
         $deleteData['DeletedBy'] = (int)$_SESSION['user_id'];
      }

      $orm->update('church_group', $deleteData, ['GroupID' => $groupId]);
      return ['status' => 'success'];
   }

   /**
    * Retrieve a single group with leader and type details
    *
    * @param int $groupId Group ID
    * @return array Group data
    */
   public static function get(int $groupId): array
   {
      $orm = new ORM();

      $result = $orm->selectWithJoin(
         baseTable: 'church_group g',
            joins: [
            ['table' => 'churchmember l', 'on' => 'g.GroupLeaderID = l.MbrID'],
            ['table' => 'group_type t',    'on' => 'g.GroupTypeID = t.GroupTypeID']
            ],
            fields: [
            'g.*',
            'l.MbrFirstName AS LeaderFirstName',
            'l.MbrFamilyName AS LeaderFamilyName',
            't.GroupTypeName',
            '(SELECT COUNT(*) FROM group_member gm WHERE gm.GroupID = g.GroupID) AS MemberCount'
            ],
         conditions: ['g.GroupID' => ':id', 'g.Deleted' => 0],
            params: [':id' => $groupId]
      );

      if (empty($result)) {
         ResponseHelper::error('Group not found', 404);
      }

      return $result[0];
   }

   /**
    * Retrieve paginated groups with filters
    *
    * @param int   $page    Page number
    * @param int   $limit   Items per page
    * @param array $filters Optional filters
    * @return array Paginated result
    */
   public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
   {
      $orm    = new ORM();
      $offset = ($page - 1) * $limit;

      $conditions = ['g.Deleted' => 0];
      $params     = [];

      if (!empty($filters['type_id'])) {
         $conditions['g.GroupTypeID'] = ':type_id';
         $params[':type_id'] = (int)$filters['type_id'];
      }
      if (!empty($filters['branch_id'])) {
         $conditions['l.BranchID'] = ':branch_id';
         $params[':branch_id'] = (int)$filters['branch_id'];
      }
      if (!empty($filters['name'])) {
            $conditions['g.GroupName LIKE'] = ':name';
         $params[':name'] = '%' . $filters['name'] . '%';
      }

      // Build ORDER BY with sorting support
      $orderBy = ['g.GroupName' => 'ASC']; // Default
      if (!empty($filters['sort_by'])) {
         $sortColumn = $filters['sort_by'];
         $sortDir = strtoupper($filters['sort_dir'] ?? 'ASC');

         // Map frontend column names to database columns
         $columnMap = [
            'GroupName' => 'g.GroupName',
            'GroupTypeName' => 't.GroupTypeName',
            'BranchName' => 'l.BranchID',
            'member_count' => 'MemberCount',
            'name' => 'g.GroupName',
            'type' => 't.GroupTypeName'
         ];

         if (isset($columnMap[$sortColumn])) {
            $orderBy = [$columnMap[$sortColumn] => ($sortDir === 'ASC' ? 'ASC' : 'DESC')];
         }
      }

      $groups = $orm->selectWithJoin(
         baseTable: 'church_group g',
            joins: [
            ['table' => 'churchmember l', 'on' => 'g.GroupLeaderID = l.MbrID'],
            ['table' => 'group_type t',    'on' => 'g.GroupTypeID = t.GroupTypeID']
            ],
            fields: [
            'g.GroupID',
            'g.GroupName',
            'g.GroupDescription',
            'g.GroupLeaderID',
            'l.MbrFirstName AS LeaderFirstName',
            'l.MbrFamilyName AS LeaderFamilyName',
            't.GroupTypeName',
            '(SELECT COUNT(*) FROM group_member gm WHERE gm.GroupID = g.GroupID) AS MemberCount'
            ],
            conditions: $conditions,
            params: $params,
         orderBy: $orderBy,
            limit: $limit,
            offset: $offset
      );

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total FROM church_group g" .
            " LEFT JOIN churchmember l ON g.GroupLeaderID = l.MbrID WHERE g.Deleted = 0" .
            (count($conditions) > 1 ? " AND " . implode(' AND ', array_slice(array_keys($conditions), 1)) : ''),
            $params
      )[0]['total'];

      return [
            'data' => $groups,
            'pagination' => [
            'page'   => $page,
            'limit'  => $limit,
            'total'  => (int)$total,
            'pages'  => (int)ceil($total / $limit)
            ]
      ];
   }

   /**
    * Add member to group
    *
    * @param int $groupId  Group ID
    * @param int $memberId Member ID
    * @return array Success response
    */
   public static function addMember(int $groupId, int $memberId): array
   {
      $orm = new ORM();

      $group = $orm->getWhere('church_group', ['GroupID' => $groupId]);
      if (empty($group)) {
         ResponseHelper::error('Group not found', 404);
      }

      // Validate member - check membership status via lookup table
      $member = $orm->selectWithJoin(
         baseTable: 'churchmember m',
         joins: [['table' => 'membership_status ms', 'on' => 'm.MbrMembershipStatusID = ms.StatusID']],
         fields: ['m.MbrID', 'ms.StatusName'],
         conditions: ['m.MbrID' => ':member_id', 'm.Deleted' => 0],
         params: [':member_id' => $memberId]
      );

      if (empty($member) || $member[0]['StatusName'] !== 'Active') {
         ResponseHelper::error('Invalid or inactive member', 400);
      }

      $existing = $orm->getWhere('group_member', ['GroupID' => $groupId, 'MbrID' => $memberId]);
      if (!empty($existing)) {
         ResponseHelper::error('Member already in group', 400);
      }

      $orm->insert('group_member', [
         'GroupID'  => $groupId,
         'MbrID'    => $memberId,
         'JoinedAt' => date('Y-m-d H:i:s')
      ]);

      return ['status' => 'success', 'group_id' => $groupId, 'member_id' => $memberId];
   }

   /**
    * Remove member from group
    *
    * @param int $groupId  Group ID
    * @param int $memberId Member ID
    * @return array Success response
    */
   public static function removeMember(int $groupId, int $memberId): array
   {
      $orm = new ORM();

      $group = $orm->getWhere('church_group', ['GroupID' => $groupId]);
      if (empty($group)) {
         ResponseHelper::error('Group not found', 404);
      }

      if ($memberId === (int)$group[0]['GroupLeaderID']) {
         ResponseHelper::error('Cannot remove group leader', 400);
      }

      $existing = $orm->getWhere('group_member', ['GroupID' => $groupId, 'MbrID' => $memberId]);
      if (empty($existing)) {
         ResponseHelper::error('Member not in group', 400);
      }

      $orm->delete('group_member', ['GroupID' => $groupId, 'MbrID' => $memberId]);

      return ['status' => 'success', 'group_id' => $groupId, 'member_id' => $memberId];
   }

   /**
    * Retrieve group members with pagination
    *
    * @param int $groupId Group ID
    * @param int $page    Page number
    * @param int $limit   Items per page
    * @return array Paginated members
    */
   public static function getMembers(int $groupId, int $page = 1, int $limit = 10): array
   {
      $orm = new ORM();
      $offset = ($page - 1) * $limit;

      $members = $orm->selectWithJoin(
         baseTable: 'group_member gm',
         joins: [['table' => 'churchmember m', 'on' => 'gm.MbrID = m.MbrID']],
         fields: [
            'm.MbrID',
            'm.MbrFirstName',
            'm.MbrFamilyName',
            'm.MbrEmailAddress',
            'm.MbrProfilePicture',
            'gm.JoinedAt'
            ],
            conditions: ['gm.GroupID' => ':group_id'],
            params: [':group_id' => $groupId],
         orderBy: ['gm.JoinedAt' => 'DESC'],
            limit: $limit,
            offset: $offset
      );

      $total = $orm->runQuery("SELECT COUNT(*) AS total FROM group_member WHERE GroupID = :id", [':id' => $groupId])[0]['total'];

      return [
            'data' => $members,
            'pagination' => [
            'page'  => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => (int)ceil($total / $limit)
            ]
      ];
   }
}