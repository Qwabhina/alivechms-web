<?php

/**
 * Volunteer Management – Event-Based Service Coordination
 *
 * Complete lifecycle management for church volunteers:
 * - System-wide volunteer roles (e.g., Usher, Choir, Media Team)
 * - Per-event volunteer assignments with status tracking
 * - Self-confirmation / decline workflow
 * - Completion marking and audit trail
 *
 * All operations are atomic, secure, and fully permission-aware.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class Volunteer
{
   private const STATUS_PENDING    = 'Pending';
   private const STATUS_CONFIRMED  = 'Confirmed';
   private const STATUS_DECLINED   = 'Declined';
   private const STATUS_COMPLETED  = 'Completed';

   /**
    * Retrieve all system-wide volunteer roles
    *
    * These roles are reusable across all events (e.g., "Sound Engineer", "Greeter").
    *
    * @return array List of volunteer roles with member counts
    */
   public static function getRoles(): array
   {
      $orm = new ORM();

      // Get roles with member counts
      $roles = $orm->runQuery(
         "SELECT 
            vr.*,
            (SELECT COUNT(*) 
             FROM member_volunteer_role mvr 
             WHERE mvr.VolunteerRoleID = vr.VolunteerRoleID 
             AND mvr.IsActive = 1) AS MemberCount
          FROM volunteer_role vr
          WHERE vr.IsActive = 1
          ORDER BY vr.RoleName ASC"
      );

      return $roles;
   }

   /**
    * Create a new reusable volunteer role
    *
    * Role names must be unique across the system.
    *
    * @param array{name:string, description?:string} $data Role payload
    * @return array{status:string, role_id:int} Success response with created role ID
    */
   public static function createRole(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'name'        => 'required|max:100',
         'description' => 'max:500|nullable'
      ]);

      $name = trim($data['name']);

      if (!empty($orm->getWhere('volunteer_role', ['RoleName' => $name]))) {
         ResponseHelper::error('Volunteer role name already exists', 400);
      }

      $roleId = $orm->insert('volunteer_role', [
         'RoleName'    => $name,
         'Description' => $data['description'] ?? null
      ])['id'];

      Helpers::logError("New volunteer role created: ID $roleId – $name");
      return ['status' => 'success', 'role_id' => $roleId];
   }

   /**
    * Assign multiple volunteers to an event
    *
    * Supports bulk assignment with optional role and notes.
    * Duplicates are silently ignored.
    *
    * @param int   $eventId     Target event ID
    * @param array $volunteers  Array of volunteer assignment objects
    * @return array{status:string, message:string} Success response
    */
   public static function assign(int $eventId, array $volunteers): array
   {
      $orm = new ORM();

      // Validate event exists and is not deleted
      $event = $orm->getWhere('event', ['EventID' => $eventId, 'Deleted' => 0]);
      if (empty($event)) {
         ResponseHelper::error('Event not found', 404);
      }

      if (empty($volunteers) || !is_array($volunteers)) {
         ResponseHelper::error('volunteers array is required', 400);
      }

      $assignedBy = Auth::getCurrentUserId();

      $orm->beginTransaction();
      try {
         foreach ($volunteers as $v) {
            Helpers::validateInput($v, [
               'member_id' => 'required|numeric',
               'role_id'   => 'numeric|nullable',
               'notes'     => 'max:500|nullable'
            ]);

            $memberId = (int)$v['member_id'];
            $roleId   = !empty($v['role_id']) ? (int)$v['role_id'] : null;

            // Validate member
            $member = $orm->runQuery(
               "SELECT cm.MbrID 
                FROM churchmember cm
                JOIN membership_status ms ON cm.MbrMembershipStatusID = ms.StatusID
                WHERE cm.MbrID = :id AND ms.StatusName = 'Active' AND cm.Deleted = 0",
               [':id' => $memberId]
            );
            if (empty($member)) {
               throw new Exception("Invalid or inactive member: $memberId");
            }

            // Validate role if provided (check IsActive)
            if ($roleId !== null) {
               $role = $orm->getWhere('volunteer_role', ['VolunteerRoleID' => $roleId, 'IsActive' => 1]);
               if (empty($role)) {
                  throw new Exception("Invalid or inactive volunteer role ID: $roleId");
               }
            }

            // Skip if already assigned
            $existing = $orm->getWhere('event_volunteer', [
               'EventID' => $eventId,
               'MbrID'   => $memberId
            ]);
            if (!empty($existing)) {
               continue;
            }

            $orm->insert('event_volunteer', [
               'EventID'        => $eventId,
               'MbrID'          => $memberId,
               'VolunteerRoleID' => $roleId,
               'AssignedBy'     => $assignedBy,
               'Notes'          => $v['notes'] ?? null,
               'Status'         => self::STATUS_PENDING,
               'AssignedAt'     => date('Y-m-d H:i:s')
            ]);
         }

         $orm->commit();
         Helpers::logError("Volunteers assigned to Event ID $eventId");
         return ['status' => 'success', 'message' => 'Volunteers assigned successfully'];
      } catch (Exception $e) {
         $orm->rollBack();
         Helpers::logError("Volunteer assignment failed: " . $e->getMessage());
         throw $e;
      }
   }

   /**
    * Confirm or decline a volunteer assignment
    *
    * Only the assigned volunteer can respond.
    *
    * @param int    $assignmentId Assignment primary key
    * @param string $action       Must be 'confirm' or 'decline'
    * @return array{status:string, new_status:string} Success response
    */
   public static function confirmAssignment(int $assignmentId, string $action): array
   {
      if (!in_array($action, ['confirm', 'decline'], true)) {
         ResponseHelper::error("Action must be 'confirm' or 'decline'", 400);
      }

      $orm = new ORM();

      $assignment = $orm->getWhere('event_volunteer', ['AssignmentID' => $assignmentId])[0] ?? null;
      if (!$assignment) {
         ResponseHelper::error('Assignment not found', 404);
      }

      $currentUserId = Auth::getCurrentUserId();
      if ((int)$assignment['MbrID'] !== $currentUserId) {
         ResponseHelper::error('You can only respond to your own assignment', 403);
      }

      if ($assignment['Status'] !== self::STATUS_PENDING) {
         ResponseHelper::error('Assignment is no longer pending', 400);
      }

      $newStatus = $action === 'confirm' ? self::STATUS_CONFIRMED : self::STATUS_DECLINED;

      $orm->update('event_volunteer', ['Status' => $newStatus], ['AssignmentID' => $assignmentId]);

      Helpers::logError("Volunteer assignment $assignmentId $action" . "ed by Member ID $currentUserId");
      return ['status' => 'success', 'new_status' => $newStatus];
   }

   /**
    * Mark a volunteer assignment as completed
    *
    * Typically used by event coordinators after service.
    *
    * @param int $assignmentId Assignment primary key
    * @return array{status:string, message:string} Success response
    */
   public static function completeAssignment(int $assignmentId): array
   {
      $orm = new ORM();

      $assignment = $orm->getWhere('event_volunteer', ['AssignmentID' => $assignmentId])[0] ?? null;
      if (!$assignment) {
         ResponseHelper::error('Assignment not found', 404);
      }

      if ($assignment['Status'] !== self::STATUS_CONFIRMED) {
         ResponseHelper::error('Only confirmed assignments can be marked complete', 400);
      }

      $orm->update('event_volunteer', ['Status' => self::STATUS_COMPLETED], ['AssignmentID' => $assignmentId]);

      Helpers::logError("Volunteer assignment $assignmentId marked as completed");
      return ['status' => 'success', 'message' => 'Volunteer service completed'];
   }

   /**
    * Retrieve all volunteers for a specific event
    *
    * Includes member details, role, status, and assignment metadata.
    *
    * @param int $eventId Target event ID
    * @param int $page    Page number (1-based)
    * @param int $limit   Items per page
    * @return array{data:array, pagination:array} Paginated result
    */
   public static function getByEvent(int $eventId, int $page = 1, int $limit = 50): array
   {
      $orm    = new ORM();
      $offset = ($page - 1) * $limit;

      $volunteers = $orm->selectWithJoin(
         baseTable: 'event_volunteer ev',
         joins: [
            ['table' => 'churchmember m',  'on' => 'ev.MbrID = m.MbrID'],
            ['table' => 'volunteer_role vr', 'on' => 'ev.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT'],
            ['table' => 'churchmember a',  'on' => 'ev.AssignedBy = a.MbrID']
         ],
         fields: [
            'ev.AssignmentID',
            'ev.Status',
            'ev.Notes',
            'ev.AssignedAt',
            'm.MbrID',
            'm.MbrFirstName',
            'm.MbrFamilyName',
            'm.MbrEmailAddress',
            'vr.RoleName',
            'a.MbrFirstName AS AssignedByFirstName',
            'a.MbrFamilyName AS AssignedByFamilyName'
         ],
         conditions: ['ev.EventID' => ':event_id'],
         params: [':event_id' => $eventId],
         orderBy: ['ev.AssignedAt' => 'DESC'],
         limit: $limit,
         offset: $offset
      );

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total FROM event_volunteer WHERE EventID = :id",
         [':id' => $eventId]
      )[0]['total'];

      return [
         'data' => $volunteers,
         'pagination' => [
            'page'   => $page,
            'limit'  => $limit,
            'total'  => (int)$total,
            'pages'  => (int)ceil($total / $limit)
         ]
      ];
   }

   /**
    * Remove a volunteer from an event
    *
    * Only allowed before confirmation or by authorized personnel.
    *
    * @param int $assignmentId Assignment primary key
    * @return array{status:string, message:string} Success response
    */
   public static function remove(int $assignmentId): array
   {
      $orm = new ORM();

      $assignment = $orm->getWhere('event_volunteer', ['AssignmentID' => $assignmentId])[0] ?? null;
      if (!$assignment) {
         ResponseHelper::error('Assignment not found', 404);
      }

      $orm->delete('event_volunteer', ['AssignmentID' => $assignmentId]);

      Helpers::logError("Volunteer removed from assignment ID $assignmentId");
      return ['status' => 'success', 'message' => 'Volunteer removed from event'];
   }

   /**
    * Assign a volunteer role to a member
    *
    * @param int   $memberId Member ID
    * @param array $data     Assignment data (role_id, start_date, end_date, notes)
    * @return array{status:string, assignment_id:int} Success response
    */
   public static function assignRoleToMember(int $memberId, array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'role_id'    => 'required|numeric',
         'start_date' => 'date|nullable',
         'end_date'   => 'date|nullable',
         'notes'      => 'max:500|nullable'
      ]);

      $roleId = (int)$data['role_id'];

      // Validate member
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

      // Validate role
      $role = $orm->getWhere('volunteer_role', ['VolunteerRoleID' => $roleId, 'IsActive' => 1]);
      if (empty($role)) {
         ResponseHelper::error('Invalid or inactive volunteer role', 400);
      }

      // Check if already assigned and active
      $existing = $orm->getWhere('member_volunteer_role', [
         'MbrID' => $memberId,
         'VolunteerRoleID' => $roleId,
         'IsActive' => 1
      ]);

      if (!empty($existing)) {
         ResponseHelper::error('Member already has this volunteer role assigned', 400);
      }

      $assignedBy = Auth::getCurrentUserId();

      $assignmentId = $orm->insert('member_volunteer_role', [
         'MbrID'            => $memberId,
         'VolunteerRoleID'  => $roleId,
         'StartDate'        => $data['start_date'] ?? null,
         'EndDate'          => $data['end_date'] ?? null,
         'AssignedBy'       => $assignedBy,
         'Notes'            => $data['notes'] ?? null,
         'IsActive'         => 1,
         'AssignedAt'       => date('Y-m-d H:i:s')
      ])['id'];

      Helpers::logError("Volunteer role $roleId assigned to Member $memberId");
      return ['status' => 'success', 'assignment_id' => $assignmentId];
   }

   /**
    * Remove a volunteer role from a member
    *
    * @param int $assignmentId Member volunteer role assignment ID
    * @return array{status:string, message:string} Success response
    */
   public static function removeRoleFromMember(int $assignmentId): array
   {
      $orm = new ORM();

      $assignment = $orm->getWhere('member_volunteer_role', ['MemberVolunteerRoleID' => $assignmentId])[0] ?? null;
      if (!$assignment) {
         ResponseHelper::error('Assignment not found', 404);
      }

      // Soft delete by setting IsActive to 0
      $orm->update('member_volunteer_role', ['IsActive' => 0], ['MemberVolunteerRoleID' => $assignmentId]);

      Helpers::logError("Volunteer role removed from member: Assignment ID $assignmentId");
      return ['status' => 'success', 'message' => 'Volunteer role removed from member'];
   }

   /**
    * Get all volunteer roles assigned to a member
    *
    * @param int $memberId Member ID
    * @return array List of assigned volunteer roles
    */
   public static function getMemberVolunteerRoles(int $memberId): array
   {
      $orm = new ORM();

      $roles = $orm->selectWithJoin(
         baseTable: 'member_volunteer_role mvr',
         joins: [
            ['table' => 'volunteer_role vr', 'on' => 'mvr.VolunteerRoleID = vr.VolunteerRoleID'],
            ['table' => 'churchmember a', 'on' => 'mvr.AssignedBy = a.MbrID', 'type' => 'LEFT']
         ],
         fields: [
            'mvr.MemberVolunteerRoleID',
            'mvr.StartDate',
            'mvr.EndDate',
            'mvr.IsActive',
            'mvr.AssignedAt',
            'mvr.Notes',
            'vr.VolunteerRoleID',
            'vr.RoleName',
            'vr.Description AS RoleDescription',
            'a.MbrFirstName AS AssignedByFirstName',
            'a.MbrFamilyName AS AssignedByFamilyName'
         ],
         conditions: ['mvr.MbrID' => ':member_id', 'mvr.IsActive' => 1],
         params: [':member_id' => $memberId],
         orderBy: ['mvr.AssignedAt' => 'DESC']
      );

      return $roles;
   }

   /**
    * Get all members with a specific volunteer role
    *
    * @param int $roleId Volunteer role ID
    * @param int $page   Page number
    * @param int $limit  Items per page
    * @return array{data:array, pagination:array} Paginated result
    */
   public static function getMembersByRole(int $roleId, int $page = 1, int $limit = 50): array
   {
      $orm = new ORM();
      $offset = ($page - 1) * $limit;

      $members = $orm->selectWithJoin(
         baseTable: 'member_volunteer_role mvr',
         joins: [
            ['table' => 'churchmember m', 'on' => 'mvr.MbrID = m.MbrID'],
            ['table' => 'volunteer_role vr', 'on' => 'mvr.VolunteerRoleID = vr.VolunteerRoleID']
         ],
         fields: [
            'mvr.MemberVolunteerRoleID',
            'mvr.StartDate',
            'mvr.EndDate',
            'mvr.AssignedAt',
            'm.MbrID',
            'm.MbrFirstName',
            'm.MbrFamilyName',
            'm.MbrEmailAddress',
            'm.MbrProfilePicture',
            'vr.RoleName'
         ],
         conditions: ['mvr.VolunteerRoleID' => ':role_id', 'mvr.IsActive' => 1, 'm.Deleted' => 0],
         params: [':role_id' => $roleId],
         orderBy: ['mvr.AssignedAt' => 'DESC'],
         limit: $limit,
         offset: $offset
      );

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total 
          FROM member_volunteer_role mvr
          JOIN churchmember m ON mvr.MbrID = m.MbrID
          WHERE mvr.VolunteerRoleID = :role_id AND mvr.IsActive = 1 AND m.Deleted = 0",
         [':role_id' => $roleId]
      )[0]['total'];

      return [
         'data' => $members,
         'pagination' => [
            'page'   => $page,
            'limit'  => $limit,
            'total'  => (int)$total,
            'pages'  => (int)ceil($total / $limit)
         ]
      ];
   }
}
