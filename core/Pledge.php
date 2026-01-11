<?php

/**
 * Pledge Management
 *
 * Full pledge lifecycle: creation, payment recording,
 * status tracking, fulfillment detection, and reporting.
 *
 * @package  AliveChMS\Core
 * @version  1.1.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class Pledge
{
   private const STATUS_ACTIVE     = 'Active';
   private const STATUS_FULFILLED  = 'Fulfilled';
   private const STATUS_CANCELLED  = 'Cancelled';

   /**
    * Get pledge statistics for a fiscal year
    */
   public static function getStats(?int $fiscalYearId = null): array
   {
      $orm = new ORM();

      // Get fiscal year
      if ($fiscalYearId) {
         $fy = $orm->runQuery(
            "SELECT FiscalYearID, FiscalYearName, Status FROM fiscalyear WHERE FiscalYearID = :id",
            [':id' => $fiscalYearId]
         );
      } else {
         $fy = $orm->runQuery(
            "SELECT FiscalYearID, FiscalYearName, Status FROM fiscalyear WHERE Status = 'Active' LIMIT 1"
         );
      }

      $fiscalYear = $fy[0] ?? null;
      $fyId = $fiscalYear['FiscalYearID'] ?? null;
      $fyCondition = $fyId ? "AND p.FiscalYearID = :fy_id" : "";
      $fyParams = $fyId ? [':fy_id' => $fyId] : [];

      // Total pledges
      $totalResult = $orm->runQuery(
         "SELECT COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count FROM pledge p WHERE 1=1 $fyCondition",
         $fyParams
      )[0];

      // By status
      $activeResult = $orm->runQuery(
         "SELECT COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count FROM pledge p WHERE PledgeStatus = 'Active' $fyCondition",
         $fyParams
      )[0];

      $fulfilledResult = $orm->runQuery(
         "SELECT COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count FROM pledge p WHERE PledgeStatus = 'Fulfilled' $fyCondition",
         $fyParams
      )[0];

      $cancelledResult = $orm->runQuery(
         "SELECT COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count FROM pledge p WHERE PledgeStatus = 'Cancelled' $fyCondition",
         $fyParams
      )[0];

      // Total payments made
      $paymentsResult = $orm->runQuery(
         "SELECT COALESCE(SUM(pp.PaymentAmount), 0) AS total, COUNT(*) AS count 
          FROM pledge_payment pp 
          JOIN pledge p ON pp.PledgeID = p.PledgeID 
          WHERE 1=1 $fyCondition",
         $fyParams
      )[0];

      // Outstanding balance (active pledges - payments)
      $outstandingResult = $orm->runQuery(
         "SELECT COALESCE(SUM(p.PledgeAmount), 0) - COALESCE((
            SELECT SUM(pp.PaymentAmount) FROM pledge_payment pp 
            JOIN pledge p2 ON pp.PledgeID = p2.PledgeID 
            WHERE p2.PledgeStatus = 'Active' " . ($fyId ? "AND p2.FiscalYearID = :fy_id2" : "") . "
         ), 0) AS outstanding
          FROM pledge p WHERE p.PledgeStatus = 'Active' $fyCondition",
         $fyId ? array_merge($fyParams, [':fy_id2' => $fyId]) : []
      )[0];

      // By pledge type
      $byType = $orm->runQuery(
         "SELECT pt.PledgeTypeID, pt.PledgeTypeName, 
                 COALESCE(SUM(p.PledgeAmount), 0) AS total, COUNT(p.PledgeID) AS count
          FROM pledge p
          JOIN pledge_type pt ON p.PledgeTypeID = pt.PledgeTypeID
          WHERE 1=1 $fyCondition
          GROUP BY pt.PledgeTypeID, pt.PledgeTypeName
          ORDER BY total DESC",
         $fyParams
      );

      // Monthly trend
      $monthlyTrend = $orm->runQuery(
         "SELECT DATE_FORMAT(PledgeDate, '%Y-%m') AS month,
                 DATE_FORMAT(PledgeDate, '%b %Y') AS month_label,
                 COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count
          FROM pledge p
          WHERE PledgeDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) $fyCondition
          GROUP BY DATE_FORMAT(PledgeDate, '%Y-%m')
          ORDER BY month ASC",
         $fyParams
      );

      // Top pledgers
      $topPledgers = $orm->runQuery(
         "SELECT m.MbrID, m.MbrFirstName, m.MbrFamilyName,
                 COALESCE(SUM(p.PledgeAmount), 0) AS total_pledged,
                 COUNT(p.PledgeID) AS pledge_count
          FROM pledge p
          JOIN churchmember m ON p.MbrID = m.MbrID
          WHERE 1=1 $fyCondition
          GROUP BY m.MbrID, m.MbrFirstName, m.MbrFamilyName
          ORDER BY total_pledged DESC
          LIMIT 10",
         $fyParams
      );

      // Overdue pledges (active with due date passed)
      $overdueResult = $orm->runQuery(
         "SELECT COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count 
          FROM pledge p 
          WHERE PledgeStatus = 'Active' AND DueDate IS NOT NULL AND DueDate < CURDATE() $fyCondition",
         $fyParams
      )[0];

      // Fulfillment rate
      $totalCount = (int)$totalResult['count'];
      $fulfilledCount = (int)$fulfilledResult['count'];
      $fulfillmentRate = $totalCount > 0 ? round(($fulfilledCount / $totalCount) * 100, 1) : 0;

      return [
         'fiscal_year' => $fiscalYear ? ['id' => $fyId, 'name' => $fiscalYear['FiscalYearName'], 'status' => $fiscalYear['Status']] : null,
         'total_amount' => (float)$totalResult['total'],
         'total_count' => $totalCount,
         'active_amount' => (float)$activeResult['total'],
         'active_count' => (int)$activeResult['count'],
         'fulfilled_amount' => (float)$fulfilledResult['total'],
         'fulfilled_count' => $fulfilledCount,
         'cancelled_amount' => (float)$cancelledResult['total'],
         'cancelled_count' => (int)$cancelledResult['count'],
         'payments_total' => (float)$paymentsResult['total'],
         'payments_count' => (int)$paymentsResult['count'],
         'outstanding_amount' => (float)$outstandingResult['outstanding'],
         'overdue_amount' => (float)$overdueResult['total'],
         'overdue_count' => (int)$overdueResult['count'],
         'fulfillment_rate' => $fulfillmentRate,
         'by_type' => $byType,
         'monthly_trend' => $monthlyTrend,
         'top_pledgers' => $topPledgers
      ];
   }

   /**
    * Create a new pledge
    *
    * @param array $data Pledge payload
    * @return array ['status' => 'success', 'pledge_id' => int]
    * @throws Exception On validation or database failure
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'member_id'       => 'required|numeric',
         'pledge_type_id'  => 'required|numeric',
         'fiscal_year_id'  => 'required|numeric',
         'amount'          => 'required|numeric',
         'pledge_date'     => 'required|date',
         'due_date'        => 'date|nullable',
         'description'     => 'max:500|nullable'
      ]);

      $memberId     = (int)$data['member_id'];
      $typeId       = (int)$data['pledge_type_id'];
      $fiscalYearId = (int)$data['fiscal_year_id'];
      $amount       = (float)$data['amount'];

      if ($amount <= 0) {
         ResponseHelper::error('Pledge amount must be greater than zero', 400);
      }

      // Validate foreign keys
      $valid = $orm->runQuery(
         "SELECT
                (SELECT COUNT(*) FROM churchmember WHERE MbrID = :mid AND Deleted = 0 AND MbrMembershipStatus = 'Active') AS member_ok,
                (SELECT COUNT(*) FROM pledge_type WHERE PledgeTypeID = :tid) AS type_ok,
                (SELECT COUNT(*) FROM fiscalyear WHERE FiscalYearID = :fyid AND Status = 'Active') AS fy_ok",
         [':mid' => $memberId, ':tid' => $typeId, ':fyid' => $fiscalYearId]
      )[0];

      if ($valid['member_ok'] == 0) ResponseHelper::error('Invalid member', 400);
      if ($valid['type_ok'] == 0)   ResponseHelper::error('Invalid pledge type', 400);
      if ($valid['fy_ok'] == 0)     ResponseHelper::error('Invalid fiscal year', 400);

      $pledgeId = $orm->insert('pledge', [
         'MbrID'          => $memberId,
         'PledgeTypeID'   => $typeId,
         'FiscalYearID'   => $fiscalYearId,
         'PledgeAmount'   => $amount,
         'PledgeDate'     => $data['pledge_date'],
         'DueDate'        => $data['due_date'] ?? null,
         'PledgeStatus'   => self::STATUS_ACTIVE,
         'Description'    => $data['description'] ?? null,
         'CreatedBy'      => Auth::getCurrentUserId(),
         'CreatedAt'      => date('Y-m-d H:i:s')
      ])['id'];

      Helpers::logError("New pledge created: PledgeID $pledgeId | Amount $amount | Member $memberId");
      return ['status' => 'success', 'pledge_id' => $pledgeId];
   }

   /**
    * Record payment against a pledge
    *
    * @param int   $pledgeId Pledge ID
    * @param array $data     Payment data
    * @return array ['status' => 'success', 'message' => string]
    * @throws Exception On failure
    */
   public static function recordPayment(int $pledgeId, array $data): array
   {
      $orm = new ORM();

      $pledge = $orm->getWhere('pledge', ['PledgeID' => $pledgeId, 'PledgeStatus' => self::STATUS_ACTIVE])[0] ?? null;
      if (!$pledge) {
         ResponseHelper::error('Active pledge not found', 404);
      }

      Helpers::validateInput($data, [
         'amount'         => 'required|numeric',
         'payment_date'   => 'required|date',
         'contribution_id' => 'numeric|nullable'
      ]);

      $paymentAmount = (float)$data['amount'];
      if ($paymentAmount <= 0) {
         ResponseHelper::error('Payment amount must be greater than zero', 400);
      }

      $orm->beginTransaction();
      try {
         $orm->insert('pledge_payment', [
            'PledgeID'       => $pledgeId,
            'ContributionID' => !empty($data['contribution_id']) ? (int)$data['contribution_id'] : null,
            'PaymentAmount'  => $paymentAmount,
            'PaymentDate'    => $data['payment_date'],
            'RecordedBy'     => Auth::getCurrentUserId(),
            'RecordedAt'     => date('Y-m-d H:i:s')
         ]);

         // Check fulfillment
         $paid = $orm->runQuery(
            "SELECT COALESCE(SUM(PaymentAmount), 0) AS paid FROM pledge_payment WHERE PledgeID = :id",
            [':id' => $pledgeId]
         )[0]['paid'];

         if ($paid >= (float)$pledge['PledgeAmount']) {
            $orm->update('pledge', ['PledgeStatus' => self::STATUS_FULFILLED], ['PledgeID' => $pledgeId]);
         }

         $orm->commit();
         return ['status' => 'success', 'message' => 'Payment recorded'];
      } catch (Exception $e) {
         $orm->rollBack();
         throw $e;
      }
   }

   /**
    * Update an existing pledge
    *
    * Allowed fields:
    * - amount (only if no payments recorded)
    * - pledge_date, due_date
    * - description
    * - pledge_type_id
    *
    * Prevents changes that would corrupt financial integrity.
    *
    * @param int   $pledgeId Pledge ID
    * @param array $data     Updated fields
    * @return array Success response
    * @throws Exception On validation or business rule violation
    */
   public static function update(int $pledgeId, array $data): array
   {
      $orm = new ORM();

      $pledge = $orm->getWhere('pledge', ['PledgeID' => $pledgeId])[0] ?? null;
      if (!$pledge) {
         ResponseHelper::error('Pledge not found', 404);
      }

      if ($pledge['PledgeStatus'] === self::STATUS_FULFILLED) {
         ResponseHelper::error('Cannot modify a fulfilled pledge', 400);
      }

      if ($pledge['PledgeStatus'] === self::STATUS_CANCELLED) {
         ResponseHelper::error('Cannot modify a cancelled pledge', 400);
      }

      $update = [];

      // Amount can only be changed if no payments exist
      if (isset($data['amount'])) {
         $newAmount = (float)$data['amount'];
         if ($newAmount <= 0) {
            ResponseHelper::error('Pledge amount must be greater than zero', 400);
         }

         $paid = $orm->runQuery(
            "SELECT COALESCE(SUM(PaymentAmount), 0) AS paid FROM pledge_payment WHERE PledgeID = :id",
            [':id' => $pledgeId]
         )[0]['paid'];

         if ($paid > 0 && $newAmount < $paid) {
            ResponseHelper::error('Cannot reduce amount below total paid', 400);
         }

         $update['PledgeAmount'] = $newAmount;
      }

      if (!empty($data['pledge_date'])) {
         $update['PledgeDate'] = $data['pledge_date'];
      }

      if (isset($data['due_date'])) {
         $update['DueDate'] = $data['due_date'] === '' ? null : $data['due_date'];
      }

      if (isset($data['description'])) {
         $update['Description'] = $data['description'] ?? null;
      }

      if (!empty($data['pledge_type_id'])) {
         $typeExists = $orm->getWhere('pledge_type', ['PledgeTypeID' => (int)$data['pledge_type_id']]);
         if (empty($typeExists)) {
            ResponseHelper::error('Invalid pledge type', 400);
         }
         $update['PledgeTypeID'] = (int)$data['pledge_type_id'];
      }

      if (empty($update)) {
         return ['status' => 'success', 'message' => 'No changes applied', 'pledge_id' => $pledgeId];
      }

      $orm->update('pledge', $update, ['PledgeID' => $pledgeId]);

      // Re-check fulfillment status after amount change
      if (isset($update['PledgeAmount'])) {
         $paid = $orm->runQuery(
            "SELECT COALESCE(SUM(PaymentAmount), 0) AS paid FROM pledge_payment WHERE PledgeID = :id",
            [':id' => $pledgeId]
         )[0]['paid'];

         $newStatus = ($paid >= $update['PledgeAmount']) ? self::STATUS_FULFILLED : self::STATUS_ACTIVE;
         if ($pledge['PledgeStatus'] !== $newStatus) {
            $orm->update('pledge', ['PledgeStatus' => $newStatus], ['PledgeID' => $pledgeId]);
         }
      }

      Helpers::logError("Pledge updated: PledgeID $pledgeId");
      return ['status' => 'success', 'pledge_id' => $pledgeId];
   }

   /**
    * Retrieve a single pledge with payment progress
    */
   public static function get(int $pledgeId): array
   {
      $orm = new ORM();

      $result = $orm->runQuery(
         "SELECT p.*, 
                 m.MbrFirstName, m.MbrFamilyName,
                 pt.PledgeTypeName,
                 fy.FiscalYearName,
                 c.MbrFirstName AS CreatorFirstName, c.MbrFamilyName AS CreatorFamilyName
          FROM pledge p
          JOIN churchmember m ON p.MbrID = m.MbrID
          JOIN pledge_type pt ON p.PledgeTypeID = pt.PledgeTypeID
          JOIN fiscalyear fy ON p.FiscalYearID = fy.FiscalYearID
          LEFT JOIN churchmember c ON p.CreatedBy = c.MbrID
          WHERE p.PledgeID = :id",
         [':id' => $pledgeId]
      );

      if (empty($result)) {
         ResponseHelper::error('Pledge not found', 404);
      }

      $payments = $orm->runQuery(
         "SELECT pp.*, r.MbrFirstName AS RecorderFirstName, r.MbrFamilyName AS RecorderFamilyName
          FROM pledge_payment pp
          LEFT JOIN churchmember r ON pp.RecordedBy = r.MbrID
          WHERE pp.PledgeID = :id
          ORDER BY pp.PaymentDate DESC",
         [':id' => $pledgeId]
      );

      $totalPaid = array_sum(array_column($payments, 'PaymentAmount'));

      $pledge = $result[0];
      $pledgeAmount = (float)$pledge['PledgeAmount'];
      $balance = $pledgeAmount - $totalPaid;
      $progress = $pledgeAmount > 0 ? round(($totalPaid / $pledgeAmount) * 100, 1) : 0;

      return [
         'PledgeID' => $pledge['PledgeID'],
         'MbrID' => $pledge['MbrID'],
         'MemberName' => $pledge['MbrFirstName'] . ' ' . $pledge['MbrFamilyName'],
         'MbrFirstName' => $pledge['MbrFirstName'],
         'MbrFamilyName' => $pledge['MbrFamilyName'],
         'PledgeTypeID' => $pledge['PledgeTypeID'],
         'PledgeTypeName' => $pledge['PledgeTypeName'],
         'FiscalYearID' => $pledge['FiscalYearID'],
         'FiscalYearName' => $pledge['FiscalYearName'],
         'PledgeAmount' => $pledgeAmount,
         'PledgeDate' => $pledge['PledgeDate'],
         'DueDate' => $pledge['DueDate'],
         'PledgeStatus' => $pledge['PledgeStatus'],
         'Description' => $pledge['Description'],
         'CreatedBy' => $pledge['CreatedBy'],
         'CreatorName' => $pledge['CreatorFirstName'] ? $pledge['CreatorFirstName'] . ' ' . $pledge['CreatorFamilyName'] : null,
         'CreatedAt' => $pledge['CreatedAt'],
         'total_paid' => $totalPaid,
         'balance' => $balance,
         'progress' => $progress,
         'payments' => $payments
      ];
   }

   /**
    * Retrieve paginated pledges with filters
    */
   public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
   {
      $orm = new ORM();
      $offset = ($page - 1) * $limit;

      $where = [];
      $params = [];

      if (!empty($filters['member_id'])) {
         $where[] = 'p.MbrID = :mid';
         $params[':mid'] = (int)$filters['member_id'];
      }
      if (!empty($filters['status'])) {
         $where[] = 'p.PledgeStatus = :status';
         $params[':status'] = $filters['status'];
      }
      if (!empty($filters['fiscal_year_id'])) {
         $where[] = 'p.FiscalYearID = :fy';
         $params[':fy'] = (int)$filters['fiscal_year_id'];
      }
      if (!empty($filters['pledge_type_id'])) {
         $where[] = 'p.PledgeTypeID = :type';
         $params[':type'] = (int)$filters['pledge_type_id'];
      }
      if (!empty($filters['start_date'])) {
         $where[] = 'p.PledgeDate >= :start';
         $params[':start'] = $filters['start_date'];
      }
      if (!empty($filters['end_date'])) {
         $where[] = 'p.PledgeDate <= :end';
         $params[':end'] = $filters['end_date'];
      }

      $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

      // Sorting
      $orderBy = 'p.PledgeDate DESC';
      if (!empty($filters['sort_by'])) {
         $columnMap = [
            'PledgeDate' => 'p.PledgeDate',
            'PledgeAmount' => 'p.PledgeAmount',
            'MemberName' => 'm.MbrFirstName',
            'PledgeStatus' => 'p.PledgeStatus',
            'DueDate' => 'p.DueDate'
         ];
         $sortCol = $columnMap[$filters['sort_by']] ?? 'p.PledgeDate';
         $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
         $orderBy = "$sortCol $sortDir";
      }

      $pledges = $orm->runQuery(
         "SELECT p.PledgeID, p.PledgeAmount, p.PledgeDate, p.DueDate, p.PledgeStatus, p.Description,
                 p.MbrID, m.MbrFirstName, m.MbrFamilyName,
                 p.PledgeTypeID, pt.PledgeTypeName,
                 p.FiscalYearID, fy.FiscalYearName,
                 COALESCE((SELECT SUM(PaymentAmount) FROM pledge_payment WHERE PledgeID = p.PledgeID), 0) AS TotalPaid
          FROM pledge p
          JOIN churchmember m ON p.MbrID = m.MbrID
          JOIN pledge_type pt ON p.PledgeTypeID = pt.PledgeTypeID
          JOIN fiscalyear fy ON p.FiscalYearID = fy.FiscalYearID
          $whereClause
          ORDER BY $orderBy
          LIMIT :limit OFFSET :offset",
         array_merge($params, [':limit' => $limit, ':offset' => $offset])
      );

      // Calculate balance and progress for each pledge
      $mapped = array_map(function ($p) {
         $amount = (float)$p['PledgeAmount'];
         $paid = (float)$p['TotalPaid'];
         $balance = $amount - $paid;
         $progress = $amount > 0 ? round(($paid / $amount) * 100, 1) : 0;

         return [
            'PledgeID' => $p['PledgeID'],
            'MbrID' => $p['MbrID'],
            'MemberName' => $p['MbrFirstName'] . ' ' . $p['MbrFamilyName'],
            'MbrFirstName' => $p['MbrFirstName'],
            'MbrFamilyName' => $p['MbrFamilyName'],
            'PledgeTypeID' => $p['PledgeTypeID'],
            'PledgeTypeName' => $p['PledgeTypeName'],
            'FiscalYearID' => $p['FiscalYearID'],
            'FiscalYearName' => $p['FiscalYearName'],
            'PledgeAmount' => $amount,
            'PledgeDate' => $p['PledgeDate'],
            'DueDate' => $p['DueDate'],
            'PledgeStatus' => $p['PledgeStatus'],
            'Description' => $p['Description'],
            'TotalPaid' => $paid,
            'Balance' => $balance,
            'Progress' => $progress
         ];
      }, $pledges);

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total FROM pledge p $whereClause",
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
}