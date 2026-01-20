<?php

/**
 * Contribution Management
 *
 * Handles creation, update, soft deletion, restoration,
 * retrieval, and reporting of member financial contributions.
 *
 * Refactored for optimized schema v2.0:
 * - Uses payment_method table (was paymentoption)
 * - FiscalYearID is now optional (nullable)
 * - Added audit trail support (DeletedBy, DeletedAt)
 * - Uses membership_status lookup table
 * - Consistent field naming
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

class Contribution
{
   /**
    * Create a new contribution record
    *
    * @param array $data Contribution payload
    * @return array ['status' => 'success', 'contribution_id' => int]
    * @throws Exception On validation or database failure
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'amount'               => 'required|numeric',
         'date'                 => 'required|date',
         'contribution_type_id' => 'required|numeric',
         'member_id'            => 'required|numeric',
         'payment_method_id'    => 'required|numeric',
         'fiscal_year_id'       => 'numeric|nullable',
         'description'          => 'max:500|nullable',
      ]);

      $amount           = (float)$data['amount'];
      $contributionDate = $data['date'];
      $memberId         = (int)$data['member_id'];
      $typeId           = (int)$data['contribution_type_id'];
      $paymentMethodId  = (int)$data['payment_method_id'];
      $fiscalYearId     = !empty($data['fiscal_year_id']) ? (int)$data['fiscal_year_id'] : null;

      if ($amount <= 0) {
         ResponseHelper::error('Contribution amount must be greater than zero', 400);
      }

      if ($contributionDate > date('Y-m-d')) {
         ResponseHelper::error('Contribution date cannot be in the future', 400);
      }

      // Validate foreign keys
      $validationQuery = "SELECT
            (SELECT COUNT(*) FROM churchmember c 
             JOIN membership_status ms ON c.MbrMembershipStatusID = ms.StatusID 
             WHERE c.MbrID = :mid AND c.Deleted = 0 AND ms.StatusName = 'Active') AS member_ok,
            (SELECT COUNT(*) FROM contribution_type WHERE ContributionTypeID = :tid AND IsActive = 1) AS type_ok,
            (SELECT COUNT(*) FROM payment_method WHERE MethodID = :pmid AND IsActive = 1) AS payment_ok";

      $validationParams = [
         ':mid' => $memberId,
         ':tid' => $typeId,
         ':pmid' => $paymentMethodId
      ];

      // Only validate fiscal year if provided
      if ($fiscalYearId !== null) {
         $validationQuery .= ",
            (SELECT COUNT(*) FROM fiscal_year WHERE FiscalYearID = :fyid AND Status = 'Active') AS fiscal_ok";
         $validationParams[':fyid'] = $fiscalYearId;
      }

      $valid = $orm->runQuery($validationQuery, $validationParams)[0];

      if ($valid['member_ok'] == 0)   ResponseHelper::error('Invalid or inactive member', 400);
      if ($valid['type_ok'] == 0)     ResponseHelper::error('Invalid contribution type', 400);
      if ($valid['payment_ok'] == 0)  ResponseHelper::error('Invalid payment method', 400);
      if ($fiscalYearId !== null && isset($valid['fiscal_ok']) && $valid['fiscal_ok'] == 0) {
         ResponseHelper::error('Invalid or inactive fiscal year', 400);
      }

      $orm->beginTransaction();
      try {
         $contributionId = $orm->insert('contribution', [
            'ContributionAmount'       => $amount,
            'ContributionDate'         => $contributionDate,
            'ContributionTypeID'       => $typeId,
            'PaymentMethodID'          => $paymentMethodId,
            'MbrID'                    => $memberId,
            'FiscalYearID'             => $fiscalYearId,
            'ContributionDescription'  => $data['description'] ?? null,
            'Deleted'                  => 0,
            'RecordedBy'               => Auth::getCurrentUserId(),
            'RecordedAt'               => date('Y-m-d H:i:s')
         ])['id'];

         $orm->commit();

         Helpers::logError("New contribution recorded: ID $contributionId | Amount $amount | Member $memberId");
         return ['status' => 'success', 'contribution_id' => $contributionId];
      } catch (Exception $e) {
         $orm->rollBack();
         Helpers::logError("Contribution creation failed: " . $e->getMessage());
         throw $e;
      }
   }

   /**
    * Update an existing contribution
    *
    * @param int   $contributionId Contribution ID
    * @param array $data           Updated data
    * @return array ['status' => 'success', 'contribution_id' => int]
    */
   public static function update(int $contributionId, array $data): array
   {
      $orm = new ORM();

      $existing = $orm->getWhere('contribution', ['ContributionID' => $contributionId, 'Deleted' => 0]);
      if (empty($existing)) {
         ResponseHelper::error('Contribution not found or deleted', 404);
      }

      Helpers::validateInput($data, [
         'amount'               => 'numeric|nullable',
         'date'                 => 'date|nullable',
         'contribution_type_id' => 'numeric|nullable',
         'payment_method_id'    => 'numeric|nullable',
         'fiscal_year_id'       => 'numeric|nullable',
         'description'          => 'max:500|nullable',
      ]);

      $update = [];

      if (isset($data['amount']) && (float)$data['amount'] > 0) {
         $update['ContributionAmount'] = (float)$data['amount'];
      }
      if (!empty($data['date'])) {
         if ($data['date'] > date('Y-m-d')) {
            ResponseHelper::error('Date cannot be in the future', 400);
         }
         $update['ContributionDate'] = $data['date'];
      }
      if (!empty($data['contribution_type_id'])) {
         $update['ContributionTypeID'] = (int)$data['contribution_type_id'];
      }
      if (!empty($data['payment_method_id'])) {
         $update['PaymentMethodID'] = (int)$data['payment_method_id'];
      }
      if (isset($data['fiscal_year_id'])) {
         $update['FiscalYearID'] = !empty($data['fiscal_year_id']) ? (int)$data['fiscal_year_id'] : null;
      }
      if (isset($data['description'])) {
         $update['ContributionDescription'] = $data['description'];
      }

      if (!empty($update)) {
         $update['UpdatedBy'] = Auth::getCurrentUserId();
         $update['UpdatedAt'] = date('Y-m-d H:i:s');
         $orm->update('contribution', $update, ['ContributionID' => $contributionId]);
      }

      return ['status' => 'success', 'contribution_id' => $contributionId];
   }

   /**
    * Soft delete a contribution
    *
    * @param int $contributionId Contribution ID
    * @return array ['status' => 'success']
    */
   public static function delete(int $contributionId): array
   {
      $orm = new ORM();

      $affected = $orm->update('contribution', [
         'Deleted' => 1,
         'DeletedBy' => Auth::getCurrentUserId(),
         'DeletedAt' => date('Y-m-d H:i:s')
      ], ['ContributionID' => $contributionId, 'Deleted' => 0]);

      if ($affected === 0) {
         ResponseHelper::error('Contribution not found or already deleted', 404);
      }

      Helpers::logError("Contribution soft-deleted: ID $contributionId by " . Auth::getCurrentUserId());
      return ['status' => 'success'];
   }

   /**
    * Restore a soft-deleted contribution
    *
    * @param int $contributionId Contribution ID
    * @return array ['status' => 'success']
    */
   public static function restore(int $contributionId): array
   {
      $orm = new ORM();

      $affected = $orm->update('contribution', ['Deleted' => 0], ['ContributionID' => $contributionId, 'Deleted' => 1]);
      if ($affected === 0) {
         ResponseHelper::error('Contribution not found or not deleted', 404);
      }

      return ['status' => 'success'];
   }

   /**
    * Retrieve a single contribution with related data
    *
    * @param int $contributionId Contribution ID
    * @return array Contribution details
    */
   public static function get(int $contributionId): array
   {
      $orm = new ORM();

      $result = $orm->selectWithJoin(
            baseTable: 'contribution c',
            joins: [
            ['table' => 'churchmember m',       'on' => 'c.MbrID = m.MbrID'],
            ['table' => 'contribution_type ct', 'on' => 'c.ContributionTypeID = ct.ContributionTypeID'],
            ['table' => 'payment_method pm',    'on' => 'c.PaymentMethodID = pm.MethodID'],
            ['table' => 'fiscal_year fy',       'on' => 'c.FiscalYearID = fy.FiscalYearID', 'type' => 'LEFT']
            ],
            fields: [
            'c.*',
            'm.MbrFirstName',
            'm.MbrFamilyName',
            'ct.ContributionTypeName',
            'pm.MethodName as PaymentMethodName',
            'fy.FiscalYearName'
            ],
         conditions: ['c.ContributionID' => ':id', 'c.Deleted' => 0],
         params: [':id' => $contributionId]
      );

      if (empty($result)) {
         ResponseHelper::error('Contribution not found', 404);
      }

      return $result[0];
   }

   /**
    * Retrieve paginated contributions with filters
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

      $conditions = ['c.Deleted' => 0];
      $params     = [];

      if (!empty($filters['contribution_type_id'])) {
         $conditions['c.ContributionTypeID'] = ':type_id';
         $params[':type_id'] = (int)$filters['contribution_type_id'];
      }
      if (!empty($filters['member_id'])) {
         $conditions['c.MbrID'] = ':member_id';
         $params[':member_id'] = (int)$filters['member_id'];
      }
      if (!empty($filters['fiscal_year_id'])) {
         $conditions['c.FiscalYearID'] = ':fy_id';
         $params[':fy_id'] = (int)$filters['fiscal_year_id'];
      }
      if (!empty($filters['start_date'])) {
         $conditions['c.ContributionDate >='] = ':start';
         $params[':start'] = $filters['start_date'];
      }
      if (!empty($filters['end_date'])) {
         $conditions['c.ContributionDate <='] = ':end';
         $params[':end'] = $filters['end_date'];
      }

      // Build ORDER BY with sorting support
      $orderBy = ['c.ContributionDate' => 'DESC']; // Default
      if (!empty($filters['sort_by'])) {
         $sortColumn = $filters['sort_by'];
         $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');

         // Map frontend column names to database columns
         $columnMap = [
            'ContributionDate' => 'c.ContributionDate',
            'ContributionAmount' => 'c.ContributionAmount',
            'MemberName' => 'm.MbrFirstName',
            'ContributionTypeName' => 'ct.ContributionTypeName',
            'date' => 'c.ContributionDate',
            'amount' => 'c.ContributionAmount',
            'member' => 'm.MbrFirstName',
            'type' => 'ct.ContributionTypeName'
         ];

         if (isset($columnMap[$sortColumn])) {
            $orderBy = [$columnMap[$sortColumn] => ($sortDir === 'ASC' ? 'ASC' : 'DESC')];
         }
      }

      $contributions = $orm->selectWithJoin(
         baseTable: 'contribution c',
         joins: [
            ['table' => 'churchmember m',       'on' => 'c.MbrID = m.MbrID'],
            ['table' => 'contribution_type ct', 'on' => 'c.ContributionTypeID = ct.ContributionTypeID'],
            ['table' => 'payment_method pm',    'on' => 'c.PaymentMethodID = pm.MethodID'],
            ['table' => 'fiscal_year fy',       'on' => 'c.FiscalYearID = fy.FiscalYearID', 'type' => 'LEFT']
         ],
         fields: [
            'c.ContributionID',
            'c.ContributionAmount',
            'c.ContributionDate',
            'c.ContributionDescription',
            'c.MbrID',
            'c.ContributionTypeID',
            'c.PaymentMethodID',
            'c.FiscalYearID',
            'm.MbrFirstName',
            'm.MbrFamilyName',
            'ct.ContributionTypeName',
            'pm.MethodName as PaymentMethodName',
            'fy.FiscalYearName'
         ],
         conditions: $conditions,
         params: $params,
         orderBy: $orderBy,
         limit: $limit,
         offset: $offset
      );

      // FIXED: Simplified total count query logic
      // Build WHERE clause for count query (excluding pagination)
      $whereConditions = ['c.Deleted = 0'];
      $countParams = [];

      if (!empty($filters['contribution_type_id'])) {
         $whereConditions[] = 'c.ContributionTypeID = :type_id';
         $countParams[':type_id'] = (int)$filters['contribution_type_id'];
      }
      if (!empty($filters['member_id'])) {
         $whereConditions[] = 'c.MbrID = :member_id';
         $countParams[':member_id'] = (int)$filters['member_id'];
      }
      if (!empty($filters['fiscal_year_id'])) {
         $whereConditions[] = 'c.FiscalYearID = :fy_id';
         $countParams[':fy_id'] = (int)$filters['fiscal_year_id'];
      }
      if (!empty($filters['start_date'])) {
         $whereConditions[] = 'c.ContributionDate >= :start';
         $countParams[':start'] = $filters['start_date'];
      }
      if (!empty($filters['end_date'])) {
         $whereConditions[] = 'c.ContributionDate <= :end';
         $countParams[':end'] = $filters['end_date'];
      }

      $whereClause = implode(' AND ', $whereConditions);

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total FROM contribution c WHERE $whereClause",
         $countParams
      )[0]['total'];

      return [
            'data' => $contributions,
            'pagination' => [
            'page'   => $page,
            'limit'  => $limit,
            'total'  => (int)$total,
            'pages'  => (int)ceil($total / $limit)
            ]
      ];
   }

   /**
    * Get total contributions with filters
    *
    * @param array $filters Filters
    * @return array ['total_contribution' => string]
    */
   public static function getTotal(array $filters = []): array
   {
      $orm = new ORM();

      $conditions = ['c.Deleted = 0'];
      $params     = [];

      if (!empty($filters['contribution_type_id'])) {
         $conditions[] = 'c.ContributionTypeID = :type_id';
         $params[':type_id'] = (int)$filters['contribution_type_id'];
      }
      if (!empty($filters['fiscal_year_id'])) {
         $conditions[] = 'c.FiscalYearID = :fy_id';
         $params[':fy_id'] = (int)$filters['fiscal_year_id'];
      }
      if (!empty($filters['start_date'])) {
         $conditions[] = 'c.ContributionDate >= :start';
         $params[':start'] = $filters['start_date'];
      }
      if (!empty($filters['end_date'])) {
         $conditions[] = 'c.ContributionDate <= :end';
         $params[':end'] = $filters['end_date'];
      }

      $whereClause = implode(' AND ', $conditions);

      $result = $orm->runQuery(
         "SELECT COALESCE(SUM(c.ContributionAmount), 0) AS total 
          FROM contribution c 
          WHERE $whereClause",
         $params
      )[0];

      return ['total_contribution' => number_format((float)$result['total'], 2)];
   }

   /**
    * Get contribution statistics for a fiscal year
    *
    * @param int|null $fiscalYearId Optional fiscal year ID (null = active fiscal year)
    * @return array Comprehensive statistics data
    */
   public static function getStats(?int $fiscalYearId = null): array
   {
      $orm = new ORM();

      // Get fiscal year - either specified or active
      if ($fiscalYearId) {
         $fy = $orm->runQuery(
            "SELECT FiscalYearID, FiscalYearName, StartDate, EndDate, Status 
             FROM fiscal_year WHERE FiscalYearID = :id",
            [':id' => $fiscalYearId]
         );
      } else {
         $fy = $orm->runQuery(
            "SELECT FiscalYearID, FiscalYearName, StartDate, EndDate, Status 
             FROM fiscal_year WHERE Status = 'Active' LIMIT 1"
         );
      }

      $fiscalYear = $fy[0] ?? null;
      $fyId = $fiscalYear['FiscalYearID'] ?? null;
      $fiscalYearName = $fiscalYear['FiscalYearName'] ?? 'All Time';
      $fyStartDate = $fiscalYear['StartDate'] ?? date('Y-01-01');
      $fyEndDate = $fiscalYear['EndDate'] ?? date('Y-12-31');
      $fyStatus = $fiscalYear['Status'] ?? 'Unknown';

      // Fiscal year filter condition
      $fyCondition = $fyId ? "AND c.FiscalYearID = :fy_id" : "";
      $fyParams = $fyId ? [':fy_id' => $fyId] : [];

      // Total contributions for fiscal year
      $fyTotalResult = $orm->runQuery(
         "SELECT 
            COALESCE(SUM(ContributionAmount), 0) AS total,
            COUNT(*) AS count
         FROM contribution c
         WHERE Deleted = 0 $fyCondition",
         $fyParams
      )[0];

      // This month (within fiscal year)
      $monthStart = date('Y-m-01');
      $monthEnd = date('Y-m-t');
      $monthResult = $orm->runQuery(
         "SELECT 
            COALESCE(SUM(ContributionAmount), 0) AS total,
            COUNT(*) AS count
         FROM contribution c
         WHERE Deleted = 0 
         AND ContributionDate >= :start 
         AND ContributionDate <= :end
         $fyCondition",
         array_merge([':start' => $monthStart, ':end' => $monthEnd], $fyParams)
      )[0];

      // Last month (within fiscal year)
      $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
      $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
      $lastMonthResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ContributionAmount), 0) AS total, COUNT(*) AS count
         FROM contribution c
         WHERE Deleted = 0 
         AND ContributionDate >= :start 
         AND ContributionDate <= :end
         $fyCondition",
         array_merge([':start' => $lastMonthStart, ':end' => $lastMonthEnd], $fyParams)
      )[0];

      // This week
      $weekStart = date('Y-m-d', strtotime('monday this week'));
      $weekEnd = date('Y-m-d', strtotime('sunday this week'));
      $weekResult = $orm->runQuery(
         "SELECT 
            COALESCE(SUM(ContributionAmount), 0) AS total,
            COUNT(*) AS count
         FROM contribution c
         WHERE Deleted = 0 
         AND ContributionDate >= :start 
         AND ContributionDate <= :end
         $fyCondition",
         array_merge([':start' => $weekStart, ':end' => $weekEnd], $fyParams)
      )[0];

      // Today
      $today = date('Y-m-d');
      $todayResult = $orm->runQuery(
         "SELECT 
            COALESCE(SUM(ContributionAmount), 0) AS total,
            COUNT(*) AS count
         FROM contribution c
         WHERE Deleted = 0 
         AND ContributionDate = :today
         $fyCondition",
         array_merge([':today' => $today], $fyParams)
      )[0];

      // Top contributors (fiscal year)
      $topContributors = $orm->runQuery(
         "SELECT 
            m.MbrID,
            m.MbrFirstName,
            m.MbrFamilyName,
            COALESCE(SUM(c.ContributionAmount), 0) AS total,
            COUNT(*) AS contribution_count
         FROM contribution c
         JOIN churchmember m ON c.MbrID = m.MbrID
         WHERE c.Deleted = 0 $fyCondition
         GROUP BY c.MbrID, m.MbrFirstName, m.MbrFamilyName
         ORDER BY total DESC
         LIMIT 10",
         $fyParams
      );

      // Contributions by type (fiscal year)
      $byType = $orm->runQuery(
         "SELECT 
            ct.ContributionTypeID,
            ct.ContributionTypeName,
            COALESCE(SUM(c.ContributionAmount), 0) AS total,
            COUNT(*) AS count
         FROM contribution c
         JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
         WHERE c.Deleted = 0 $fyCondition
         GROUP BY ct.ContributionTypeID, ct.ContributionTypeName
         ORDER BY total DESC",
         $fyParams
      );

      // Contributions by payment method (fiscal year)
      $byPaymentMethod = $orm->runQuery(
         "SELECT 
            pm.MethodID,
            pm.MethodName,
            COALESCE(SUM(c.ContributionAmount), 0) AS total,
            COUNT(*) AS count
         FROM contribution c
         JOIN payment_method pm ON c.PaymentMethodID = pm.MethodID
         WHERE c.Deleted = 0 $fyCondition
         GROUP BY pm.MethodID, pm.MethodName
         ORDER BY total DESC",
         $fyParams
      );

      // Monthly trend (last 12 months or fiscal year months)
      $monthlyTrend = $orm->runQuery(
         "SELECT 
            DATE_FORMAT(ContributionDate, '%Y-%m') AS month,
            DATE_FORMAT(ContributionDate, '%b %Y') AS month_label,
            COALESCE(SUM(ContributionAmount), 0) AS total,
            COUNT(*) AS count
         FROM contribution c
         WHERE Deleted = 0 
         AND ContributionDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         $fyCondition
         GROUP BY DATE_FORMAT(ContributionDate, '%Y-%m')
         ORDER BY month ASC",
         $fyParams
      );

      // Unique contributors count (fiscal year)
      $uniqueContributors = $orm->runQuery(
         "SELECT COUNT(DISTINCT MbrID) AS count
         FROM contribution c
         WHERE Deleted = 0 $fyCondition",
         $fyParams
      )[0]['count'];

      // Calculate statistics
      $fyTotal = (float)$fyTotalResult['total'];
      $fyCount = (int)$fyTotalResult['count'];
      $avgAmount = $fyCount > 0 ? $fyTotal / $fyCount : 0;

      $monthTotal = (float)$monthResult['total'];
      $lastMonthTotal = (float)$lastMonthResult['total'];
      $monthGrowth = $lastMonthTotal > 0 ? (($monthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;

      // Average per contributor
      $avgPerContributor = $uniqueContributors > 0 ? $fyTotal / $uniqueContributors : 0;

      return [
         'fiscal_year' => [
            'id' => $fyId,
            'name' => $fiscalYearName,
            'start_date' => $fyStartDate,
            'end_date' => $fyEndDate,
            'status' => $fyStatus
         ],
         'total_amount' => $fyTotal,
         'total_count' => $fyCount,
         'average_amount' => round($avgAmount, 2),
         'average_per_contributor' => round($avgPerContributor, 2),
         'unique_contributors' => (int)$uniqueContributors,
         'month_total' => $monthTotal,
         'month_count' => (int)$monthResult['count'],
         'month_growth' => round($monthGrowth, 1),
         'last_month_total' => $lastMonthTotal,
         'week_total' => (float)$weekResult['total'],
         'week_count' => (int)$weekResult['count'],
         'today_total' => (float)$todayResult['total'],
         'today_count' => (int)$todayResult['count'],
         'top_contributors' => $topContributors,
         'by_type' => $byType,
         'by_payment_method' => $byPaymentMethod,
         'monthly_trend' => $monthlyTrend
      ];
   }

   /**
    * Generate receipt data for a contribution
    *
    * @param int $contributionId Contribution ID
    * @return array Receipt data
    */
   public static function getReceipt(int $contributionId): array
   {
      $orm = new ORM();

      // Get contribution with all related data
      $result = $orm->runQuery(
         "SELECT 
            c.*,
            m.MbrFirstName,
            m.MbrFamilyName,
            m.MbrEmailAddress,
            m.MbrProfilePicture,
            ct.ContributionTypeName,
            pm.MethodName as PaymentMethodName,
            fy.FiscalYearName,
            b.BranchName,
            b.BranchAddress,
            b.BranchPhoneNumber,
            b.BranchEmailAddress
         FROM contribution c
         JOIN churchmember m ON c.MbrID = m.MbrID
         JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
         JOIN payment_method pm ON c.PaymentMethodID = pm.MethodID
         LEFT JOIN fiscal_year fy ON c.FiscalYearID = fy.FiscalYearID
         LEFT JOIN branch b ON m.BranchID = b.BranchID
         WHERE c.ContributionID = :id AND c.Deleted = 0",
         [':id' => $contributionId]
      );

      if (empty($result)) {
         ResponseHelper::error('Contribution not found', 404);
      }

      $contribution = $result[0];

      // Generate receipt number (format: RCP-YYYY-XXXXX)
      $receiptNumber = sprintf('RCP-%s-%05d', date('Y'), $contributionId);

      return [
         'receipt_number' => $receiptNumber,
         'contribution_id' => $contribution['ContributionID'],
         'date' => $contribution['ContributionDate'],
         'amount' => (float)$contribution['ContributionAmount'],
         'type' => $contribution['ContributionTypeName'],
         'payment_method' => $contribution['PaymentMethodName'],
         'fiscal_year' => $contribution['FiscalYearName'],
         'description' => $contribution['ContributionDescription'],
         'recorded_at' => $contribution['RecordedAt'],
         'member' => [
            'id' => $contribution['MbrID'],
            'name' => $contribution['MbrFirstName'] . ' ' . $contribution['MbrFamilyName'],
            'email' => $contribution['MbrEmailAddress']
         ],
         'church' => [
            'name' => $contribution['BranchName'] ?? 'Church Name',
            'address' => $contribution['BranchAddress'] ?? '',
            'phone' => $contribution['BranchPhone'] ?? '',
            'email' => $contribution['BranchEmail'] ?? ''
         ],
         'generated_at' => date('Y-m-d H:i:s')
      ];
   }

   /**
    * Get member contribution statement for a fiscal year
    *
    * @param int $memberId Member ID
    * @param int|null $fiscalYearId Fiscal Year ID (null for active)
    * @return array Statement data
    */
   public static function getMemberStatement(int $memberId, ?int $fiscalYearId = null): array
   {
      $orm = new ORM();

      // Get member info
      $member = $orm->runQuery(
         "SELECT m.*, b.BranchName, b.BranchAddress, b.BranchPhoneNumber, b.BranchEmailAddress
          FROM churchmember m
          LEFT JOIN branch b ON m.BranchID = b.BranchID
          WHERE m.MbrID = :id AND m.Deleted = 0",
         [':id' => $memberId]
      );

      if (empty($member)) {
         ResponseHelper::error('Member not found', 404);
      }

      $member = $member[0];

      // Get fiscal year
      if ($fiscalYearId) {
         $fy = $orm->runQuery(
            "SELECT * FROM fiscal_year WHERE FiscalYearID = :id",
            [':id' => $fiscalYearId]
         );
      } else {
         $fy = $orm->runQuery(
            "SELECT * FROM fiscal_year WHERE Status = 'Active' LIMIT 1"
         );
      }

      $fiscalYear = $fy[0] ?? null;
      $fyCondition = $fiscalYear ? "AND c.FiscalYearID = :fy_id" : "";
      $fyParams = $fiscalYear ? [':fy_id' => $fiscalYear['FiscalYearID']] : [];

      // Get all contributions for this member in the fiscal year
      $contributions = $orm->runQuery(
         "SELECT 
            c.ContributionID,
            c.ContributionAmount,
            c.ContributionDate,
            c.ContributionDescription,
            ct.ContributionTypeName,
            pm.MethodName as PaymentMethodName
         FROM contribution c
         JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
         JOIN payment_method pm ON c.PaymentMethodID = pm.MethodID
         WHERE c.MbrID = :member_id AND c.Deleted = 0 $fyCondition
         ORDER BY c.ContributionDate DESC",
         array_merge([':member_id' => $memberId], $fyParams)
      );

      // Get totals by type
      $totalsByType = $orm->runQuery(
         "SELECT 
            ct.ContributionTypeName,
            COALESCE(SUM(c.ContributionAmount), 0) AS total,
            COUNT(*) AS count
         FROM contribution c
         JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
         WHERE c.MbrID = :member_id AND c.Deleted = 0 $fyCondition
         GROUP BY ct.ContributionTypeID, ct.ContributionTypeName
         ORDER BY total DESC",
         array_merge([':member_id' => $memberId], $fyParams)
      );

      // Calculate grand total
      $grandTotal = array_reduce($contributions, function ($sum, $c) {
         return $sum + (float)$c['ContributionAmount'];
      }, 0);

      return [
         'statement_number' => sprintf('STM-%s-%05d', date('Y'), $memberId),
         'generated_at' => date('Y-m-d H:i:s'),
         'fiscal_year' => $fiscalYear ? [
            'id' => $fiscalYear['FiscalYearID'],
            'name' => $fiscalYear['FiscalYearName'],
            'start_date' => $fiscalYear['StartDate'],
            'end_date' => $fiscalYear['EndDate']
         ] : null,
         'member' => [
            'id' => $member['MbrID'],
            'name' => $member['MbrFirstName'] . ' ' . $member['MbrFamilyName'],
            'email' => $member['MbrEmailAddress']
         ],
         'church' => [
            'name' => $member['BranchName'] ?? 'Church Name',
            'address' => $member['BranchAddress'] ?? '',
            'phone' => $member['BranchPhone'] ?? '',
            'email' => $member['BranchEmail'] ?? ''
         ],
         'contributions' => $contributions,
         'totals_by_type' => $totalsByType,
         'grand_total' => $grandTotal,
         'contribution_count' => count($contributions)
      ];
   }

   /**
    * Get contribution types
    *
    * @return array List of contribution types
    */
   public static function getTypes(): array
   {
      $orm = new ORM();
      return $orm->runQuery(
         "SELECT ContributionTypeID, ContributionTypeName, ContributionTypeDescription, IsActive, IsTaxDeductible 
          FROM contribution_type 
          WHERE IsActive = 1 
          ORDER BY ContributionTypeName"
      );
   }

   /**
    * Get payment methods
    *
    * @return array List of payment methods
    */
   public static function getPaymentMethods(): array
   {
      $orm = new ORM();
      return $orm->runQuery(
         "SELECT MethodID, MethodName, DisplayOrder, IsActive 
          FROM payment_method 
          WHERE IsActive = 1 
          ORDER BY DisplayOrder"
      );
   }
}