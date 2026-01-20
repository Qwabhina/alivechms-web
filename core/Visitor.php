<?php

/**
 * Visitor Management System
 *
 * Complete visitor tracking and follow-up coordination:
 * - Record first-time visitors with contact details
 * - Track visit history and count
 * - Assign follow-up to members
 * - Convert visitors to members
 * - Generate visitor statistics
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

class Visitor
{
   /**
    * Create a new visitor record
    *
    * @param array $data Visitor payload
    * @return array ['status' => 'success', 'visitor_id' => int]
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'first_name'  => 'required|max:100',
         'last_name'   => 'required|max:100',
         'email'       => 'email|nullable',
         'phone'       => 'max:20|nullable',
         'visit_date'  => 'required|date',
         'source'      => 'in:Walk-in,Invitation,Event,Online,Other|nullable',
         'branch_id'   => 'required|numeric',
         'notes'       => 'max:1000|nullable'
      ]);

      $branchId = (int)$data['branch_id'];
      self::validateBranch($branchId);

      $visitorId = $orm->insert('visitor', [
         'FirstName'              => trim($data['first_name']),
         'LastName'               => trim($data['last_name']),
         'EmailAddress'           => !empty($data['email']) ? trim($data['email']) : null,
         'PhoneNumber'            => !empty($data['phone']) ? trim($data['phone']) : null,
         'FirstVisitDate'         => $data['visit_date'],
         'LastVisitDate'          => $data['visit_date'],
         'VisitCount'             => 1,
         'Source'                 => $data['source'] ?? 'Walk-in',
         'InterestedInMembership' => !empty($data['interested_in_membership']) ? 1 : 0,
         'AssignedFollowUpPerson' => null,
         'Notes'                  => $data['notes'] ?? null,
         'BranchID'               => $branchId,
         'ConvertedToMemberID'    => null,
         'ConvertedAt'            => null,
         'CreatedAt'              => date('Y-m-d H:i:s'),
         'UpdatedAt'              => date('Y-m-d H:i:s')
      ])['id'];

      Helpers::logError("New visitor recorded: ID $visitorId | {$data['first_name']} {$data['last_name']}");
      return ['status' => 'success', 'visitor_id' => $visitorId];
   }

   /**
    * Update an existing visitor
    *
    * @param int   $visitorId Visitor ID
    * @param array $data      Updated data
    * @return array ['status' => 'success', 'visitor_id' => int]
    */
   public static function update(int $visitorId, array $data): array
   {
      $orm = new ORM();

      $visitor = $orm->getWhere('visitor', ['VisitorID' => $visitorId]);
      if (empty($visitor)) {
         ResponseHelper::error('Visitor not found', 404);
      }

      $update = [];

      if (!empty($data['first_name'])) {
         $update['FirstName'] = trim($data['first_name']);
      }
      if (!empty($data['last_name'])) {
         $update['LastName'] = trim($data['last_name']);
      }
      if (isset($data['email'])) {
         $update['EmailAddress'] = !empty($data['email']) ? trim($data['email']) : null;
      }
      if (isset($data['phone'])) {
         $update['PhoneNumber'] = !empty($data['phone']) ? trim($data['phone']) : null;
      }
      if (!empty($data['first_visit_date'])) {
         $update['FirstVisitDate'] = $data['first_visit_date'];
      }
      if (!empty($data['last_visit_date'])) {
         $update['LastVisitDate'] = $data['last_visit_date'];
      }
      if (isset($data['visit_count'])) {
         $update['VisitCount'] = (int)$data['visit_count'];
      }
      if (!empty($data['source'])) {
         $update['Source'] = $data['source'];
      }
      if (isset($data['interested_in_membership'])) {
         $update['InterestedInMembership'] = !empty($data['interested_in_membership']) ? 1 : 0;
      }
      if (isset($data['notes'])) {
         $update['Notes'] = $data['notes'];
      }
      if (!empty($data['branch_id'])) {
         self::validateBranch((int)$data['branch_id']);
         $update['BranchID'] = (int)$data['branch_id'];
      }

      if (!empty($update)) {
         $update['UpdatedAt'] = date('Y-m-d H:i:s');
         $orm->update('visitor', $update, ['VisitorID' => $visitorId]);
      }

      return ['status' => 'success', 'visitor_id' => $visitorId];
   }

   /**
    * Delete a visitor (hard delete)
    *
    * @param int $visitorId Visitor ID
    * @return array ['status' => 'success']
    */
   public static function delete(int $visitorId): array
   {
      $orm = new ORM();

      $visitor = $orm->getWhere('visitor', ['VisitorID' => $visitorId]);
      if (empty($visitor)) {
         ResponseHelper::error('Visitor not found', 404);
      }

      // Check if already converted to member
      if (!empty($visitor[0]['ConvertedToMemberID'])) {
         ResponseHelper::error('Cannot delete visitor who has been converted to member', 400);
      }

      $orm->delete('visitor', ['VisitorID' => $visitorId]);

      return ['status' => 'success'];
   }

   /**
    * Get a single visitor with details
    *
    * @param int $visitorId Visitor ID
    * @return array Visitor data
    */
   public static function get(int $visitorId): array
   {
      $orm = new ORM();

      $result = $orm->selectWithJoin(
         baseTable: 'visitor v',
         joins: [
            ['table' => 'branch b', 'on' => 'v.BranchID = b.BranchID', 'type' => 'LEFT'],
            ['table' => 'churchmember m', 'on' => 'v.AssignedFollowUpPerson = m.MbrID', 'type' => 'LEFT'],
            ['table' => 'churchmember cm', 'on' => 'v.ConvertedToMemberID = cm.MbrID', 'type' => 'LEFT']
         ],
         fields: [
            'v.*',
            'b.BranchName',
            'm.MbrFirstName AS AssignedFirstName',
            'm.MbrFamilyName AS AssignedLastName',
            'cm.MbrFirstName AS ConvertedMemberFirstName',
            'cm.MbrFamilyName AS ConvertedMemberLastName'
         ],
         conditions: ['v.VisitorID' => ':id'],
         params: [':id' => $visitorId]
      );

      if (empty($result)) {
         ResponseHelper::error('Visitor not found', 404);
      }

      return $result[0];
   }

   /**
    * Get all visitors with pagination and filters
    *
    * @param int   $page    Page number
    * @param int   $limit   Items per page
    * @param array $filters Optional filters
    * @return array Paginated result
    */
   public static function getAll(int $page = 1, int $limit = 25, array $filters = []): array
   {
      $orm = new ORM();
      $offset = ($page - 1) * $limit;

      $conditions = [];
      $params = [];

      if (!empty($filters['branch_id'])) {
         $conditions['v.BranchID'] = ':branch_id';
         $params[':branch_id'] = (int)$filters['branch_id'];
      }
      if (!empty($filters['source'])) {
         $conditions['v.Source'] = ':source';
         $params[':source'] = $filters['source'];
      }
      if (!empty($filters['assigned_to'])) {
         $conditions['v.AssignedFollowUpPerson'] = ':assigned_to';
         $params[':assigned_to'] = (int)$filters['assigned_to'];
      }
      if (isset($filters['converted'])) {
         if ($filters['converted']) {
            $conditions['v.ConvertedToMemberID IS NOT NULL'] = '';
         } else {
            $conditions['v.ConvertedToMemberID IS NULL'] = '';
         }
      }
      if (isset($filters['interested_in_membership'])) {
         $conditions['v.InterestedInMembership'] = ':interested';
         $params[':interested'] = $filters['interested_in_membership'] ? 1 : 0;
      }
      if (!empty($filters['start_date'])) {
         $conditions['v.FirstVisitDate >='] = ':start_date';
         $params[':start_date'] = $filters['start_date'];
      }
      if (!empty($filters['end_date'])) {
         $conditions['v.FirstVisitDate <='] = ':end_date';
         $params[':end_date'] = $filters['end_date'];
      }
      if (!empty($filters['search'])) {
         $conditions['(v.FirstName LIKE :search OR v.LastName LIKE :search OR v.EmailAddress LIKE :search OR v.PhoneNumber LIKE :search)'] = '';
         $params[':search'] = '%' . $filters['search'] . '%';
      }

      $visitors = $orm->selectWithJoin(
         baseTable: 'visitor v',
         joins: [
            ['table' => 'branch b', 'on' => 'v.BranchID = b.BranchID', 'type' => 'LEFT'],
            ['table' => 'churchmember m', 'on' => 'v.AssignedFollowUpPerson = m.MbrID', 'type' => 'LEFT']
         ],
         fields: [
            'v.VisitorID',
            'v.FirstName',
            'v.LastName',
            'v.EmailAddress',
            'v.PhoneNumber',
            'v.FirstVisitDate',
            'v.LastVisitDate',
            'v.VisitCount',
            'v.Source',
            'v.InterestedInMembership',
            'v.ConvertedToMemberID',
            'v.ConvertedAt',
            'v.CreatedAt',
            'b.BranchName',
            'm.MbrFirstName AS AssignedFirstName',
            'm.MbrFamilyName AS AssignedLastName'
         ],
         conditions: $conditions,
         params: $params,
         orderBy: ['v.FirstVisitDate' => 'DESC'],
         limit: $limit,
         offset: $offset
      );

      $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', array_map(function ($k) {
         if (strpos($k, '(') === 0 || strpos($k, 'IS') !== false) return $k;
         return "$k = " . (strpos($k, ':') !== false ? $k : ":$k");
      }, array_keys($conditions))) : '';

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total FROM visitor v $whereClause",
         $params
      )[0]['total'];

      return [
         'data' => $visitors,
         'pagination' => [
            'page'  => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => (int)ceil($total / $limit)
         ]
      ];
   }

   /**
    * Assign follow-up to a member
    *
    * @param int $visitorId Visitor ID
    * @param int $memberId  Member ID to assign
    * @return array ['status' => 'success']
    */
   public static function assignFollowUp(int $visitorId, int $memberId): array
   {
      $orm = new ORM();

      $visitor = $orm->getWhere('visitor', ['VisitorID' => $visitorId]);
      if (empty($visitor)) {
         ResponseHelper::error('Visitor not found', 404);
      }

      // Validate member exists and is active
      $member = $orm->runQuery(
         "SELECT cm.MbrID 
          FROM churchmember cm
          JOIN membership_status ms ON cm.MbrMembershipStatusID = ms.StatusID
          WHERE cm.MbrID = :id AND ms.StatusName = 'Active' AND cm.Deleted = 0",
         [':id' => $memberId]
      );
      if (empty($member)) {
         ResponseHelper::error('Invalid or inactive member', 400);
      }

      $orm->update('visitor', [
         'AssignedFollowUpPerson' => $memberId,
         'UpdatedAt' => date('Y-m-d H:i:s')
      ], ['VisitorID' => $visitorId]);

      Helpers::logError("Visitor $visitorId assigned to Member $memberId for follow-up");
      return ['status' => 'success'];
   }

   /**
    * Record a return visit
    *
    * @param int    $visitorId Visitor ID
    * @param string $visitDate Visit date
    * @return array ['status' => 'success']
    */
   public static function recordReturnVisit(int $visitorId, string $visitDate): array
   {
      $orm = new ORM();

      $visitor = $orm->getWhere('visitor', ['VisitorID' => $visitorId]);
      if (empty($visitor)) {
         ResponseHelper::error('Visitor not found', 404);
      }

      $currentCount = (int)$visitor[0]['VisitCount'];

      $orm->update('visitor', [
         'LastVisitDate' => $visitDate,
         'VisitCount' => $currentCount + 1,
         'UpdatedAt' => date('Y-m-d H:i:s')
      ], ['VisitorID' => $visitorId]);

      return ['status' => 'success', 'visit_count' => $currentCount + 1];
   }

   /**
    * Get visitor statistics
    *
    * @param array $filters Optional filters (branch_id, start_date, end_date)
    * @return array Statistics data
    */
   public static function getStats(array $filters = []): array
   {
      $orm = new ORM();

      $where = [];
      $params = [];

      if (!empty($filters['branch_id'])) {
         $where[] = 'v.BranchID = :branch_id';
         $params[':branch_id'] = (int)$filters['branch_id'];
      }
      if (!empty($filters['start_date'])) {
         $where[] = 'v.FirstVisitDate >= :start_date';
         $params[':start_date'] = $filters['start_date'];
      }
      if (!empty($filters['end_date'])) {
         $where[] = 'v.FirstVisitDate <= :end_date';
         $params[':end_date'] = $filters['end_date'];
      }

      $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

      // Total visitors
      $total = $orm->runQuery("SELECT COUNT(*) AS count FROM visitor v $whereClause", $params)[0]['count'];

      // By source
      $bySource = $orm->runQuery(
         "SELECT Source, COUNT(*) AS count 
          FROM visitor v $whereClause 
          GROUP BY Source 
          ORDER BY count DESC",
         $params
      );

      // Interested in membership
      $interested = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM visitor v $whereClause AND v.InterestedInMembership = 1",
         $params
      )[0]['count'];

      // Converted to members
      $converted = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM visitor v $whereClause AND v.ConvertedToMemberID IS NOT NULL",
         $params
      )[0]['count'];

      // Monthly trend (last 12 months)
      $monthlyTrend = $orm->runQuery(
         "SELECT DATE_FORMAT(FirstVisitDate, '%Y-%m') AS month,
                 DATE_FORMAT(FirstVisitDate, '%b %Y') AS month_label,
                 COUNT(*) AS count
          FROM visitor v
          WHERE FirstVisitDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(FirstVisitDate, '%Y-%m')
          ORDER BY month ASC"
      );

      // Conversion rate
      $conversionRate = $total > 0 ? round(($converted / $total) * 100, 2) : 0;

      // Return visitors (visited more than once)
      $returnVisitors = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM visitor v $whereClause AND v.VisitCount > 1",
         $params
      )[0]['count'];

      return [
         'total_visitors' => (int)$total,
         'by_source' => $bySource,
         'interested_in_membership' => (int)$interested,
         'converted_count' => (int)$converted,
         'conversion_rate' => $conversionRate,
         'return_visitors' => (int)$returnVisitors,
         'monthly_trend' => $monthlyTrend
      ];
   }

   /**
    * Convert visitor to member
    *
    * Creates a new member record from visitor data and marks visitor as converted
    *
    * @param int   $visitorId Visitor ID
    * @param array $data      Additional member data
    * @return array ['status' => 'success', 'member_id' => int]
    */
   public static function convertToMember(int $visitorId, array $data): array
   {
      $orm = new ORM();

      $visitor = $orm->getWhere('visitor', ['VisitorID' => $visitorId]);
      if (empty($visitor)) {
         ResponseHelper::error('Visitor not found', 404);
      }

      $v = $visitor[0];

      // Check if already converted
      if (!empty($v['ConvertedToMemberID'])) {
         ResponseHelper::error('Visitor has already been converted to member', 400);
      }

      // Validate required member data
      Helpers::validateInput($data, [
         'date_of_birth'       => 'required|date',
         'gender'              => 'required|in:Male,Female',
         'membership_status_id' => 'required|numeric',
         'marital_status_id'   => 'required|numeric'
      ]);

      $orm->beginTransaction();
      try {
         // Create member record
         $memberId = $orm->insert('churchmember', [
            'MbrFirstName'              => $v['FirstName'],
            'MbrFamilyName'             => $v['LastName'],
            'MbrEmailAddress'           => $v['EmailAddress'],
            'MbrPhoneNumber'            => $v['PhoneNumber'],
            'MbrDateOfBirth'            => $data['date_of_birth'],
            'MbrGender'                 => $data['gender'],
            'MbrMembershipStatusID'     => (int)$data['membership_status_id'],
            'MbrMaritalStatusID'        => (int)$data['marital_status_id'],
            'BranchID'                  => $v['BranchID'],
            'MbrAddress'                => $data['address'] ?? null,
            'MbrOccupation'             => $data['occupation'] ?? null,
            'MbrEducationLevelID'       => !empty($data['education_level_id']) ? (int)$data['education_level_id'] : null,
            'MbrRegistrationDate'       => date('Y-m-d'),
            'CreatedBy'                 => Auth::getCurrentUserId(),
            'CreatedAt'                 => date('Y-m-d H:i:s'),
            'Deleted'                   => 0
         ])['id'];

         // Update visitor record
         $orm->update('visitor', [
            'ConvertedToMemberID' => $memberId,
            'ConvertedAt' => date('Y-m-d H:i:s'),
            'UpdatedAt' => date('Y-m-d H:i:s')
         ], ['VisitorID' => $visitorId]);

         $orm->commit();

         Helpers::logError("Visitor $visitorId converted to Member $memberId");
         return ['status' => 'success', 'member_id' => $memberId, 'message' => 'Visitor successfully converted to member'];
      } catch (Exception $e) {
         $orm->rollBack();
         Helpers::logError("Visitor conversion failed: " . $e->getMessage());
         throw $e;
      }
   }

   /** Private Helpers */

   private static function validateBranch(int $branchId): void
   {
      $orm = new ORM();
      $branch = $orm->getWhere('branch', ['BranchID' => $branchId]);
      if (empty($branch)) {
         ResponseHelper::error('Invalid branch ID', 400);
      }
      if ($branch[0]['IsActive'] != 1) {
         ResponseHelper::error('Branch is not active', 400);
      }
   }
}
