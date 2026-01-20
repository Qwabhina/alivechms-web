<?php

/**
 * Permission Audit Logger
 *
 * Provides comprehensive audit trail for all RBAC changes.
 * Essential for compliance, security investigations, and accountability.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 */

declare(strict_types=1);

class PermissionAudit
{
   /**
    * Log a permission-related action
    *
    * @param string $actionType Type of action performed
    * @param int $performedBy User ID who performed the action
    * @param array $details Additional details (role_id, permission_id, member_id, old_value, new_value)
    * @return void
    */
   public static function log(string $actionType, int $performedBy, array $details = []): void
   {
      $orm = new ORM();

      $data = [
         'ActionType'        => $actionType,
         'PerformedBy'       => $performedBy,
         'TargetRoleID'      => $details['role_id'] ?? null,
         'TargetPermissionID' => $details['permission_id'] ?? null,
         'TargetMemberID'    => $details['member_id'] ?? null,
         'OldValue'          => isset($details['old_value']) ? json_encode($details['old_value']) : null,
         'NewValue'          => isset($details['new_value']) ? json_encode($details['new_value']) : null,
         'IPAddress'         => Helpers::getClientIp(),
         'UserAgent'         => $_SERVER['HTTP_USER_AGENT'] ?? null
      ];

      try {
         $orm->insert('permission_audit', $data);
      } catch (Exception $e) {
         // Don't fail the main operation if audit logging fails
         Helpers::logError("[PermissionAudit] Failed to log action: " . $e->getMessage());
      }
   }

   /**
    * Get audit trail for a specific role
    *
    * @param int $roleId Role ID
    * @param int $limit Number of records to return
    * @return array Audit records
    */
   public static function getRoleAudit(int $roleId, int $limit = 50): array
   {
      $orm = new ORM();

      return $orm->selectWithJoin(
         baseTable: 'permission_audit pa',
         joins: [
            ['table' => 'churchmember cm', 'on' => 'pa.PerformedBy = cm.MbrID', 'type' => 'LEFT']
         ],
         fields: ['pa.*', 'cm.FirstName', 'cm.FamilyName'],
         conditions: ['pa.TargetRoleID' => ':role_id'],
         params: [':role_id' => $roleId],
         orderBy: ['pa.CreatedAt' => 'DESC'],
         limit: $limit
      );
   }

   /**
    * Get audit trail for a specific member
    *
    * @param int $memberId Member ID
    * @param int $limit Number of records to return
    * @return array Audit records
    */
   public static function getMemberAudit(int $memberId, int $limit = 50): array
   {
      $orm = new ORM();

      return $orm->selectWithJoin(
         baseTable: 'permission_audit pa',
         joins: [
            ['table' => 'churchmember cm', 'on' => 'pa.PerformedBy = cm.MbrID', 'type' => 'LEFT']
         ],
         fields: ['pa.*', 'cm.FirstName', 'cm.FamilyName'],
         conditions: ['pa.TargetMemberID' => ':member_id'],
         params: [':member_id' => $memberId],
         orderBy: ['pa.CreatedAt' => 'DESC'],
         limit: $limit
      );
   }

   /**
    * Get recent audit trail
    *
    * @param int $limit Number of records to return
    * @param array $filters Optional filters (action_type, performed_by, date_from, date_to)
    * @return array Audit records
    */
   public static function getRecentAudit(int $limit = 100, array $filters = []): array
   {
      $orm = new ORM();

      $conditions = [];
      $params = [];

      if (!empty($filters['action_type'])) {
         $conditions['pa.ActionType'] = ':action_type';
         $params[':action_type'] = $filters['action_type'];
      }

      if (!empty($filters['performed_by'])) {
         $conditions['pa.PerformedBy'] = ':performed_by';
         $params[':performed_by'] = $filters['performed_by'];
      }

      if (!empty($filters['date_from'])) {
         $conditions['pa.CreatedAt >='] = ':date_from';
         $params[':date_from'] = $filters['date_from'];
      }

      if (!empty($filters['date_to'])) {
         $conditions['pa.CreatedAt <='] = ':date_to';
         $params[':date_to'] = $filters['date_to'];
      }

      return $orm->selectWithJoin(
         baseTable: 'permission_audit pa',
         joins: [
            ['table' => 'churchmember cm', 'on' => 'pa.PerformedBy = cm.MbrID', 'type' => 'LEFT'],
            ['table' => 'churchrole cr', 'on' => 'pa.TargetRoleID = cr.RoleID', 'type' => 'LEFT'],
            ['table' => 'permission p', 'on' => 'pa.TargetPermissionID = p.PermissionID', 'type' => 'LEFT']
         ],
         fields: [
            'pa.*',
            'cm.FirstName AS PerformerFirstName',
            'cm.FamilyName AS PerformerFamilyName',
            'cr.RoleName',
            'p.PermissionName'
         ],
         conditions: $conditions,
         params: $params,
         orderBy: ['pa.CreatedAt' => 'DESC'],
         limit: $limit
      );
   }
}
