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

class MemberMilestone
{
   /**
    * Get milestone statistics
    */
   public static function getStats(?int $year = null): array
   {
      $orm = new ORM();
      $currentYear = $year ?? (int)date('Y');

      // Total milestones
      $totalResult = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM member_milestone WHERE Deleted = 0"
      )[0];

      // This year
      $yearResult = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM member_milestone 
          WHERE Deleted = 0 AND YEAR(MilestoneDate) = :year",
         [':year' => $currentYear]
      )[0];

      // This month
      $monthResult = $orm->runQuery(
         "SELECT COUNT(*) AS count FROM member_milestone 
          WHERE Deleted = 0 AND YEAR(MilestoneDate) = :year AND MONTH(MilestoneDate) = :month",
         [':year' => $currentYear, ':month' => (int)date('m')]
      )[0];

      // By type
      $byType = $orm->runQuery(
         "SELECT mt.MilestoneTypeID, mt.MilestoneTypeName, mt.Icon, mt.Color, COUNT(mm.MilestoneID) AS count
          FROM milestone_type mt
          LEFT JOIN member_milestone mm ON mt.MilestoneTypeID = mm.MilestoneTypeID AND mm.Deleted = 0
          WHERE mt.IsActive = 1
          GROUP BY mt.MilestoneTypeID, mt.MilestoneTypeName, mt.Icon, mt.Color
          ORDER BY count DESC"
      );

      // Monthly trend (last 12 months)
      $monthlyTrend = $orm->runQuery(
         "SELECT DATE_FORMAT(MilestoneDate, '%Y-%m') AS month,
                 DATE_FORMAT(MilestoneDate, '%b %Y') AS month_label,
                 COUNT(*) AS count
          FROM member_milestone
          WHERE Deleted = 0 AND MilestoneDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(MilestoneDate, '%Y-%m')
          ORDER BY month ASC"
      );

      // Recent milestones
      $recent = $orm->runQuery(
         "SELECT mm.MilestoneID, mm.MilestoneDate, mt.MilestoneTypeName, mt.Icon, mt.Color,
                 m.MbrFirstName, m.MbrFamilyName
          FROM member_milestone mm
          JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
          JOIN churchmember m ON mm.MbrID = m.MbrID
          WHERE mm.Deleted = 0
          ORDER BY mm.MilestoneDate DESC, mm.RecordedAt DESC
          LIMIT 10"
      );

      return [
         'total_count' => (int)$totalResult['count'],
         'year_count' => (int)$yearResult['count'],
         'month_count' => (int)$monthResult['count'],
         'current_year' => $currentYear,
         'by_type' => $byType,
         'monthly_trend' => $monthlyTrend,
         'recent' => $recent
      ];
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
                 mt.MilestoneTypeName, mt.Icon, mt.Color,
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
         'MilestoneID' => $m['MilestoneID'],
         'MbrID' => $m['MbrID'],
         'MemberName' => $m['MbrFirstName'] . ' ' . $m['MbrFamilyName'],
         'MbrFirstName' => $m['MbrFirstName'],
         'MbrFamilyName' => $m['MbrFamilyName'],
         'MbrEmailAddress' => $m['MbrEmailAddress'],
         'MilestoneTypeID' => $m['MilestoneTypeID'],
         'MilestoneTypeName' => $m['MilestoneTypeName'],
         'Icon' => $m['Icon'],
         'Color' => $m['Color'],
         'MilestoneDate' => $m['MilestoneDate'],
         'Location' => $m['Location'],
         'OfficiatingPastor' => $m['OfficiatingPastor'],
         'CertificateNumber' => $m['CertificateNumber'],
         'Notes' => $m['Notes'],
         'RecordedBy' => $m['RecordedBy'],
         'RecorderName' => $m['RecorderFirstName'] ? $m['RecorderFirstName'] . ' ' . $m['RecorderFamilyName'] : null,
         'RecordedAt' => $m['RecordedAt']
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
         $where[] = "(m.MbrFirstName LIKE :search OR m.MbrFamilyName LIKE :search OR mt.MilestoneTypeName LIKE :search)";
         $params[':search'] = '%' . $filters['search'] . '%';
      }

      $whereClause = 'WHERE ' . implode(' AND ', $where);

      // Sorting
      $orderBy = 'mm.MilestoneDate DESC, mm.RecordedAt DESC';
      if (!empty($filters['sort_by'])) {
         $columnMap = [
            'MilestoneDate' => 'mm.MilestoneDate',
            'MemberName' => 'm.MbrFirstName',
            'MilestoneTypeName' => 'mt.MilestoneTypeName'
         ];
         $sortCol = $columnMap[$filters['sort_by']] ?? 'mm.MilestoneDate';
         $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
         $orderBy = "$sortCol $sortDir";
      }

      $milestones = $orm->runQuery(
         "SELECT mm.MilestoneID, mm.MbrID, mm.MilestoneTypeID, mm.MilestoneDate, 
                 mm.Location, mm.OfficiatingPastor, mm.CertificateNumber, mm.Notes,
                 mm.RecordedAt,
                 mt.MilestoneTypeName, mt.Icon, mt.Color,
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
            'MilestoneID' => $m['MilestoneID'],
            'MbrID' => $m['MbrID'],
            'MemberName' => $m['MbrFirstName'] . ' ' . $m['MbrFamilyName'],
            'MbrFirstName' => $m['MbrFirstName'],
            'MbrFamilyName' => $m['MbrFamilyName'],
            'MilestoneTypeID' => $m['MilestoneTypeID'],
            'MilestoneTypeName' => $m['MilestoneTypeName'],
            'Icon' => $m['Icon'],
            'Color' => $m['Color'],
            'MilestoneDate' => $m['MilestoneDate'],
            'Location' => $m['Location'],
            'OfficiatingPastor' => $m['OfficiatingPastor'],
            'CertificateNumber' => $m['CertificateNumber'],
            'Notes' => $m['Notes'],
            'RecordedAt' => $m['RecordedAt']
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
         "SELECT mm.*, mt.MilestoneTypeName, mt.Icon, mt.Color,
                 r.MbrFirstName AS RecorderFirstName, r.MbrFamilyName AS RecorderFamilyName
          FROM member_milestone mm
          JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
          LEFT JOIN churchmember r ON mm.RecordedBy = r.MbrID
          WHERE mm.MbrID = :member_id AND mm.Deleted = 0
          ORDER BY mm.MilestoneDate DESC",
         [':member_id' => $memberId]
      );

      return ['data' => $milestones];
   }
}
