<?php

/**
 * Member Milestone Management
 *
 * Full lifecycle management for member milestones:
 * - Record milestones (baptism, marriage, salvation, etc.)
 * - Track certificates and officiating pastors
 * - Generate statistics and reports
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/ORM.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Database.php';

class MemberMilestone
{
   /**
    * Get milestone statistics
    */
   public static function getStats(array $filters = []): array
   {
      $orm = new ORM();

      // Build dynamic WHERE clause
      $whereConditions = ["mm.Deleted = 0"];
      $params = [];

      if (!empty($filters['start_date'])) {
         $whereConditions[] = "mm.MilestoneDate >= :startDate";
         $params[':startDate'] = $filters['start_date'];
      }

      if (!empty($filters['end_date'])) {
         $whereConditions[] = "mm.MilestoneDate <= :endDate";
         $params[':endDate'] = $filters['end_date'];
      }

      if (!empty($filters['type_id'])) {
         $whereConditions[] = "mm.MilestoneTypeID = :typeID";
         $params[':typeID'] = $filters['type_id'];
      }

      // Handle legacy year parameter if passed in filters
      if (empty($filters['start_date']) && empty($filters['end_date']) && !empty($filters['year'])) {
         $whereConditions[] = "YEAR(mm.MilestoneDate) = :year";
         $params[':year'] = $filters['year'];
      }

      $whereClause = implode(" AND ", $whereConditions);

      // 1. Total milestones (Filtered)
      $totalResult = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM member_milestone mm WHERE $whereClause",
         $params
      );
      $totalCount = !empty($totalResult) ? (int)$totalResult[0]['count'] : 0;

      // 2. By Type (Filtered)
      $byType = $orm->runQuery(
         "SELECT mt.MilestoneTypeID, mt.TypeName, mt.Icon, mt.Color, COUNT(mm.MilestoneID) AS count
          FROM milestone_type mt
          LEFT JOIN member_milestone mm ON mt.MilestoneTypeID = mm.MilestoneTypeID AND $whereClause
          WHERE mt.IsActive = 1
          GROUP BY mt.MilestoneTypeID, mt.TypeName, mt.Icon, mt.Color
          HAVING count > 0
          ORDER BY count DESC",
         $params
      );

      // 3. Recent milestones (Filtered)
      $recent = $orm->runQuery(
         "SELECT mm.MilestoneID, mm.MilestoneDate, mt.TypeName, mt.Icon, mt.Color,
                 m.MbrFirstName, m.MbrFamilyName
          FROM member_milestone mm
          JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
          JOIN churchmember m ON mm.MbrID = m.MbrID
          WHERE $whereClause
          ORDER BY mm.MilestoneDate DESC, mm.RecordedAt DESC
          LIMIT 10",
         $params
      );

      // 4. Anniversaries this month (Always current month)
      $anniversariesResult = $orm->runQuery(
         "SELECT COUNT(*) AS count 
          FROM member_milestone 
          WHERE Deleted = 0 
          AND MONTH(MilestoneDate) = :month",
         [':month' => (int)date('m')]
      );

      $anniversariesCount = !empty($anniversariesResult) ? (int)$anniversariesResult[0]['count'] : 0;

      // 5. Year Count (Current Year or Selected Year)
      $targetYear = !empty($filters['year']) ? (int)$filters['year'] : (int)date('Y');
      $yearResult = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM member_milestone WHERE Deleted = 0 AND YEAR(MilestoneDate) = :year",
         [':year' => $targetYear]
      );
      $yearCount = !empty($yearResult) ? (int)$yearResult[0]['count'] : 0;

      // 6. Month Count (Current Month - Milestones occurring this month)
      $monthResult = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM member_milestone 
          WHERE Deleted = 0 
          AND YEAR(MilestoneDate) = :year 
          AND MONTH(MilestoneDate) = :month",
         [':year' => (int)date('Y'), ':month' => (int)date('m')]
      );
      $monthCount = !empty($monthResult) ? (int)$monthResult[0]['count'] : 0;

      return [
         'total_count' => $totalCount,
         'year_count' => $yearCount,
         'month_count' => $monthCount,
         'current_year' => $targetYear,
         'anniversaries_this_month' => $anniversariesCount,
         'by_type' => array_map(function ($row) {
            return [
               'id' => $row['MilestoneTypeID'],
               'name' => $row['TypeName'],
               'icon' => $row['Icon'],
               'color' => $row['Color'],
               'count' => (int)$row['count']
            ];
         }, $byType),
         'recent' => array_map(function ($row) {
            return [
               'id' => $row['MilestoneID'],
               'date' => $row['MilestoneDate'],
               'type' => $row['TypeName'],
               'type_icon' => $row['Icon'],
               'type_color' => $row['Color'],
               'member_name' => $row['MbrFirstName'] . ' ' . $row['MbrFamilyName']
            ];
         }, $recent)
      ];
   }

   /**
    * Get upcoming anniversaries
    */
   public static function getUpcomingAnniversaries(int $days = 30, int $limit = 10): array
   {
      $orm = new ORM();

      // Calculate future date
      $futureDate = date('Y-m-d', strtotime("+$days days"));

      // We need to find milestones where the month/day is within the range
      // This is complex in SQL across year boundaries, but for simplified "next 30 days":
      // We can check if DATE_FORMAT(date, '%m-%d') is between today and future date
      // Note: This simple logic breaks across year-end (Dec-Jan). 
      // Robust solution: Add current year to the milestone month/day and check diff.

      $query = "
         SELECT mm.MilestoneID, mm.MilestoneDate, mm.MbrID,
                mt.TypeName, mt.Icon, mt.Color,
                m.MbrFirstName, m.MbrFamilyName, m.MbrProfilePicture
         FROM member_milestone mm
         JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
         JOIN churchmember m ON mm.MbrID = m.MbrID
         WHERE mm.Deleted = 0
         AND (
            DATE_ADD(mm.MilestoneDate, INTERVAL YEAR(CURDATE()) - YEAR(mm.MilestoneDate) + IF(DATE_FORMAT(CURDATE(), '%m%d') > DATE_FORMAT(mm.MilestoneDate, '%m%d'), 1, 0) YEAR) 
            BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
         )
         ORDER BY DATE_FORMAT(mm.MilestoneDate, '%m%d') ASC
         LIMIT :limit
      ";

      $anniversaries = $orm->runQuery($query, [
         ':days' => $days,
         ':limit' => $limit
      ]);

      return array_map(function ($row) {
         $milestoneDate = new DateTime($row['MilestoneDate']);
         $today = new DateTime();
         $currentYear = (int)$today->format('Y');

         // Calculate next anniversary date
         $anniversaryDate = new DateTime($currentYear . '-' . $milestoneDate->format('m-d'));
         if ($anniversaryDate < $today) {
            $anniversaryDate->modify('+1 year');
         }

         $years = $anniversaryDate->format('Y') - $milestoneDate->format('Y');
         $daysUntil = $today->diff($anniversaryDate)->days;

         return [
            'id' => $row['MilestoneID'],
            'member_id' => $row['MbrID'],
            'member_name' => $row['MbrFirstName'] . ' ' . $row['MbrFamilyName'],
            'member_photo' => $row['MbrProfilePicture'],
            'milestone_type' => $row['TypeName'],
            'type_icon' => $row['Icon'],
            'type_color' => $row['Color'],
            'date' => $row['MilestoneDate'],
            'years' => $years,
            'anniversary_display' => $anniversaryDate->format('M j, Y'),
            'days_until' => $daysUntil
         ];
      }, $anniversaries);
   }

   /**
    * Create a new milestone
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'member_id' => 'required|numeric',
         'milestone_type_id' => 'required|numeric',
         'milestone_date' => 'required|date',
         'location' => 'max:200|nullable',
         'officiating_pastor' => 'max:150|nullable',
         'certificate_number' => 'max:100|nullable',
         'notes' => 'max:1000|nullable'
      ]);

      $memberId = (int)$data['member_id'];
      $typeId = (int)$data['milestone_type_id'];

      // Validate member exists
      $member = $orm->runQuery(
         "SELECT MbrID FROM churchmember WHERE MbrID = :id AND Deleted = 0",
         [':id' => $memberId]
      );
      if (empty($member)) {
         ResponseHelper::error('Member not found', 400);
      }

      // Validate milestone type exists
      $type = $orm->runQuery(
         "SELECT MilestoneTypeID FROM milestone_type WHERE MilestoneTypeID = :id AND IsActive = 1",
         [':id' => $typeId]
      );
      if (empty($type)) {
         ResponseHelper::error('Invalid milestone type', 400);
      }

      // Check for duplicate (Same Member, Same Type, Same Date)
      $duplicate = $orm->runQuery(
         "SELECT MilestoneID FROM member_milestone 
          WHERE MbrID = :mbr_id 
          AND MilestoneTypeID = :type_id 
          AND MilestoneDate = :date 
          AND Deleted = 0",
         [
            ':mbr_id' => $memberId,
            ':type_id' => $typeId,
            ':date' => $data['milestone_date']
         ]
      );

      if (!empty($duplicate)) {
         ResponseHelper::error('This milestone has already been recorded for this member on this date', 400);
      }

      $milestoneId = $orm->insert('member_milestone', [
         'MbrID' => $memberId,
         'MilestoneTypeID' => $typeId,
         'MilestoneDate' => $data['milestone_date'],
         'Location' => $data['location'] ?? null,
         'OfficiatingPastor' => $data['officiating_pastor'] ?? null,
         'CertificateNumber' => $data['certificate_number'] ?? null,
         'Notes' => $data['notes'] ?? null,
         'RecordedBy' => Auth::getCurrentUserId(),
         'RecordedAt' => date('Y-m-d H:i:s'),
         'Deleted' => 0
      ])['id'];

      Helpers::logError("New milestone recorded: MilestoneID $milestoneId | Member $memberId | Type $typeId");
      return ['status' => 'success', 'milestone_id' => $milestoneId];
   }

   /**
    * Update an existing milestone
    */
   public static function update(int $milestoneId, array $data): array
   {
      $orm = new ORM();

      $milestone = $orm->runQuery(
         "SELECT * FROM member_milestone WHERE MilestoneID = :id AND Deleted = 0",
         [':id' => $milestoneId]
      );
      if (empty($milestone)) {
         ResponseHelper::error('Milestone not found', 404);
      }

      $update = [];

      if (!empty($data['member_id'])) {
         $memberId = (int)$data['member_id'];
         $member = $orm->runQuery(
            "SELECT MbrID FROM churchmember WHERE MbrID = :id AND Deleted = 0",
            [':id' => $memberId]
         );
         if (empty($member)) {
            ResponseHelper::error('Member not found', 400);
         }
         $update['MbrID'] = $memberId;
      }

      if (!empty($data['milestone_type_id'])) {
         $typeId = (int)$data['milestone_type_id'];
         $type = $orm->runQuery(
            "SELECT MilestoneTypeID FROM milestone_type WHERE MilestoneTypeID = :id AND IsActive = 1",
            [':id' => $typeId]
         );
         if (empty($type)) {
            ResponseHelper::error('Invalid milestone type', 400);
         }
         $update['MilestoneTypeID'] = $typeId;
      }

      if (!empty($data['milestone_date'])) {
         $update['MilestoneDate'] = $data['milestone_date'];
      }

      if (isset($data['location'])) {
         $update['Location'] = $data['location'] ?: null;
      }

      if (isset($data['officiating_pastor'])) {
         $update['OfficiatingPastor'] = $data['officiating_pastor'] ?: null;
      }

      if (isset($data['certificate_number'])) {
         $update['CertificateNumber'] = $data['certificate_number'] ?: null;
      }

      if (isset($data['notes'])) {
         $update['Notes'] = $data['notes'] ?: null;
      }

      if (!empty($update)) {
         $orm->update('member_milestone', $update, ['MilestoneID' => $milestoneId]);
      }

      return ['status' => 'success', 'milestone_id' => $milestoneId];
   }

   /**
    * Soft delete a milestone
    */
   public static function delete(int $milestoneId): array
   {
      $orm = new ORM();

      $milestone = $orm->runQuery(
         "SELECT MilestoneID FROM member_milestone WHERE MilestoneID = :id AND Deleted = 0",
         [':id' => $milestoneId]
      );
      if (empty($milestone)) {
         ResponseHelper::error('Milestone not found', 404);
      }

      $orm->update('member_milestone', ['Deleted' => 1], ['MilestoneID' => $milestoneId]);
      return ['status' => 'success'];
   }

   /**
    * Get a single milestone with details
    */
   public static function get(int $milestoneId): array
   {
      $orm = new ORM();

      $result = $orm->runQuery(
         "SELECT mm.*, 
                 mt.TypeName, mt.Icon, mt.Color,
                 m.MbrFirstName, m.MbrFamilyName, m.MbrEmailAddress,
                 r.MbrFirstName AS RecorderFirstName, r.MbrFamilyName AS RecorderFamilyName
          FROM member_milestone mm
          JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
          JOIN churchmember m ON mm.MbrID = m.MbrID
          LEFT JOIN churchmember r ON mm.RecordedBy = r.MbrID
          WHERE mm.MilestoneID = :id AND mm.Deleted = 0",
         [':id' => $milestoneId]
      );

      if (empty($result)) {
         ResponseHelper::error('Milestone not found', 404);
      }

      $m = $result[0];
      return [
         'id' => $m['MilestoneID'],
         'member_id' => $m['MbrID'],
         'member_name' => $m['MbrFirstName'] . ' ' . $m['MbrFamilyName'],
         'type_id' => $m['MilestoneTypeID'],
         'type_name' => $m['TypeName'],
         'type_icon' => $m['Icon'],
         'type_color' => $m['Color'],
         'date' => $m['MilestoneDate'],
         'location' => $m['Location'],
         'officiating_pastor' => $m['OfficiatingPastor'],
         'certificate_number' => $m['CertificateNumber'],
         'notes' => $m['Notes'],
         'recorded_by' => $m['RecordedBy'],
         'recorder_name' => $m['RecorderFirstName'] ? $m['RecorderFirstName'] . ' ' . $m['RecorderFamilyName'] : null,
         'recorded_at' => $m['RecordedAt']
      ];
   }

   /**
    * Get all milestones with pagination and filters
    */
   public static function getAll(int $page = 1, int $limit = 25, array $filters = []): array
   {
      $orm = new ORM();
      $offset = ($page - 1) * $limit;

      $where = ['mm.Deleted = 0'];
      $params = [];

      if (!empty($filters['member_id'])) {
         $where[] = 'mm.MbrID = :member_id';
         $params[':member_id'] = (int)$filters['member_id'];
      }

      if (!empty($filters['milestone_type_id'])) {
         $where[] = 'mm.MilestoneTypeID = :type_id';
         $params[':type_id'] = (int)$filters['milestone_type_id'];
      }

      if (!empty($filters['year'])) {
         $where[] = 'YEAR(mm.MilestoneDate) = :year';
         $params[':year'] = (int)$filters['year'];
      }

      if (!empty($filters['start_date'])) {
         $where[] = 'mm.MilestoneDate >= :start_date';
         $params[':start_date'] = $filters['start_date'];
      }

      if (!empty($filters['end_date'])) {
         $where[] = 'mm.MilestoneDate <= :end_date';
         $params[':end_date'] = $filters['end_date'];
      }

      if (!empty($filters['search'])) {
         $where[] = "(m.MbrFirstName LIKE :search OR m.MbrFamilyName LIKE :search OR mt.TypeName LIKE :search)";
         $params[':search'] = '%' . $filters['search'] . '%';
      }

      $whereClause = 'WHERE ' . implode(' AND ', $where);

      // Sorting
      $orderBy = 'mm.MilestoneDate DESC, mm.RecordedAt DESC';
      if (!empty($filters['sort_by'])) {
         $columnMap = [
            'date' => 'mm.MilestoneDate',
            'member_name' => 'm.MbrFirstName',
            'type_name' => 'mt.TypeName'
         ];
         $sortCol = $columnMap[$filters['sort_by']] ?? 'mm.MilestoneDate';
         $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
         $orderBy = "$sortCol $sortDir";
      }

      $milestones = $orm->runQuery(
         "SELECT mm.*,
                 mt.TypeName, mt.Icon, mt.Color,
                 m.MbrFirstName, m.MbrFamilyName
          FROM member_milestone mm
          JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
          JOIN churchmember m ON mm.MbrID = m.MbrID
          $whereClause
          ORDER BY $orderBy
          LIMIT :limit OFFSET :offset",
         array_merge($params, [':limit' => $limit, ':offset' => $offset])
      );

      $mapped = array_map(function ($m) {
         return [
            'id' => $m['MilestoneID'],
            'member_id' => $m['MbrID'],
            'member_name' => $m['MbrFirstName'] . ' ' . $m['MbrFamilyName'],
            'type_id' => $m['MilestoneTypeID'],
            'type_name' => $m['TypeName'],
            'type_icon' => $m['Icon'],
            'type_color' => $m['Color'],
            'date' => $m['MilestoneDate'],
            'location' => $m['Location'],
            'officiating_pastor' => $m['OfficiatingPastor'],
            'certificate_number' => $m['CertificateNumber'],
            'notes' => $m['Notes'],
            'recorded_at' => $m['RecordedAt']
         ];
      }, $milestones);

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total 
          FROM member_milestone mm
          JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
          JOIN churchmember m ON mm.MbrID = m.MbrID
          $whereClause",
         $params
      )[0]['total'];

      return [
         'data' => $mapped,
         'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => (int)ceil($total / $limit)
         ]
      ];
   }

   /**
    * Get milestones for a specific member
    */
   public static function getByMember(int $memberId): array
   {
      $orm = new ORM();

      $milestones = $orm->runQuery(
         "SELECT mm.*, mt.TypeName,
                 r.MbrFirstName AS RecorderFirstName, r.MbrFamilyName AS RecorderFamilyName
          FROM member_milestone mm
          JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
          LEFT JOIN churchmember r ON mm.RecordedBy = r.MbrID
          WHERE mm.MbrID = :member_id AND mm.Deleted = 0
          ORDER BY mm.MilestoneDate DESC",
         [':member_id' => $memberId]
      );

      return ['data' => array_map(function ($m) {
         return [
            'id' => $m['MilestoneID'],
            'type_id' => $m['MilestoneTypeID'],
            'type_name' => $m['TypeName'],
            'type_icon' => $m['Icon'],
            'type_color' => $m['Color'],
            'date' => $m['MilestoneDate'],
            'location' => $m['Location'],
            'officiating_pastor' => $m['OfficiatingPastor'],
            'certificate_number' => $m['CertificateNumber'],
            'notes' => $m['Notes'],
            'recorder_name' => $m['RecorderFirstName'] ? $m['RecorderFirstName'] . ' ' . $m['RecorderFamilyName'] : null,
         ];
      }, $milestones)];
   }
}
