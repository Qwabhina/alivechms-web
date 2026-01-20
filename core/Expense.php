<?php

/**
 * Expense Management
 *
 * Complete expense lifecycle with request, approval workflow,
 * cancellation, fiscal-year integration, and full audit trail.
 *
 * Refactored for optimized schema v2.0:
 * - FiscalYearID is now optional (nullable)
 * - Added audit trail support (DeletedBy, DeletedAt, UpdatedBy, UpdatedAt)
 * - Uses fiscal_year table (was fiscalyear)
 * - Enhanced approval workflow tracking
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

class Expense
{
   private const STATUS_PENDING  = 'Pending Approval';
   private const STATUS_APPROVED = 'Approved';
   private const STATUS_DECLINED = 'Declined';

   /**
    * Get expense statistics for a fiscal year
    */
   public static function getStats(?int $fiscalYearId = null): array
   {
      $orm = new ORM();

      // Get fiscal year
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
      $fyStatus = $fiscalYear['Status'] ?? 'Unknown';

      $fyCondition = $fyId ? "AND e.FiscalYearID = :fy_id" : "";
      $fyParams = $fyId ? [':fy_id' => $fyId] : [];

      // Total expenses
      $fyTotalResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE 1=1 $fyCondition",
         $fyParams
      )[0];

      // Approved
      $approvedResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE ExpStatus = 'Approved' $fyCondition",
         $fyParams
      )[0];

      // Pending
      $pendingResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE ExpStatus = 'Pending Approval' $fyCondition",
         $fyParams
      )[0];

      // Declined
      $declinedResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE ExpStatus = 'Declined' $fyCondition",
         $fyParams
      )[0];

      // This month
      $monthStart = date('Y-m-01');
      $monthEnd = date('Y-m-t');
      $monthResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE ExpDate >= :start AND ExpDate <= :end $fyCondition",
         array_merge([':start' => $monthStart, ':end' => $monthEnd], $fyParams)
      )[0];

      // Last month
      $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
      $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
      $lastMonthResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE ExpDate >= :start AND ExpDate <= :end $fyCondition",
         array_merge([':start' => $lastMonthStart, ':end' => $lastMonthEnd], $fyParams)
      )[0];

      // This week
      $weekStart = date('Y-m-d', strtotime('monday this week'));
      $weekEnd = date('Y-m-d', strtotime('sunday this week'));
      $weekResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE ExpDate >= :start AND ExpDate <= :end $fyCondition",
         array_merge([':start' => $weekStart, ':end' => $weekEnd], $fyParams)
      )[0];

      // Today
      $today = date('Y-m-d');
      $todayResult = $orm->runQuery(
         "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE ExpDate = :today $fyCondition",
         array_merge([':today' => $today], $fyParams)
      )[0];

      // By category
      $byCategory = $orm->runQuery(
         "SELECT ec.ExpCategoryID, ec.ExpCategoryName AS CategoryName,
                 COALESCE(SUM(e.ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e
          JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
          WHERE 1=1 $fyCondition
          GROUP BY ec.ExpCategoryID, ec.ExpCategoryName
          ORDER BY total DESC",
         $fyParams
      );

      // By status
      $byStatus = $orm->runQuery(
         "SELECT ExpStatus, COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e WHERE 1=1 $fyCondition
          GROUP BY ExpStatus ORDER BY total DESC",
         $fyParams
      );

      // By branch
      $byBranch = $orm->runQuery(
         "SELECT b.BranchID, b.BranchName, COALESCE(SUM(e.ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e
          LEFT JOIN branch b ON e.BranchID = b.BranchID
          WHERE 1=1 $fyCondition
          GROUP BY b.BranchID, b.BranchName
          ORDER BY total DESC",
         $fyParams
      );

      // Monthly trend
      $monthlyTrend = $orm->runQuery(
         "SELECT DATE_FORMAT(ExpDate, '%Y-%m') AS month,
                 DATE_FORMAT(ExpDate, '%b %Y') AS month_label,
                 COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
          FROM expense e
          WHERE ExpDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) $fyCondition
          GROUP BY DATE_FORMAT(ExpDate, '%Y-%m')
          ORDER BY month ASC",
         $fyParams
      );

      // Top expenses
      $topExpenses = $orm->runQuery(
         "SELECT e.ExpID, e.ExpTitle, e.ExpAmount, e.ExpDate, e.ExpStatus,
                 ec.ExpCategoryName AS CategoryName, b.BranchName
          FROM expense e
          JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
          LEFT JOIN branch b ON e.BranchID = b.BranchID
          WHERE 1=1 $fyCondition
          ORDER BY e.ExpAmount DESC LIMIT 10",
         $fyParams
      );

      $fyTotal = (float)$fyTotalResult['total'];
      $fyCount = (int)$fyTotalResult['count'];
      $avgAmount = $fyCount > 0 ? $fyTotal / $fyCount : 0;
      $monthTotal = (float)$monthResult['total'];
      $lastMonthTotal = (float)$lastMonthResult['total'];
      $monthGrowth = $lastMonthTotal > 0 ? (($monthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;

      return [
         'fiscal_year' => ['id' => $fyId, 'name' => $fiscalYearName, 'status' => $fyStatus],
         'total_amount' => $fyTotal,
         'total_count' => $fyCount,
         'average_amount' => round($avgAmount, 2),
         'approved_total' => (float)$approvedResult['total'],
         'approved_count' => (int)$approvedResult['count'],
         'pending_total' => (float)$pendingResult['total'],
         'pending_count' => (int)$pendingResult['count'],
         'rejected_total' => (float)$declinedResult['total'],
         'rejected_count' => (int)$declinedResult['count'],
         'month_total' => $monthTotal,
         'month_count' => (int)$monthResult['count'],
         'month_growth' => round($monthGrowth, 1),
         'last_month_total' => $lastMonthTotal,
         'week_total' => (float)$weekResult['total'],
         'week_count' => (int)$weekResult['count'],
         'today_total' => (float)$todayResult['total'],
         'today_count' => (int)$todayResult['count'],
         'by_category' => $byCategory,
         'by_status' => $byStatus,
         'by_branch' => $byBranch,
         'monthly_trend' => $monthlyTrend,
         'top_expenses' => $topExpenses
      ];
   }

   /**
    * Create a new expense request
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'title'          => 'required|max:100',
         'amount'         => 'required|numeric',
         'expense_date'   => 'required|date',
         'category_id'    => 'required|numeric',
         'fiscal_year_id' => 'numeric|nullable',
         'purpose'        => 'max:1000|nullable',
      ]);

      $amount = (float)$data['amount'];
      $expenseDate = $data['expense_date'];
      $categoryId = (int)$data['category_id'];
      $fiscalYearId = !empty($data['fiscal_year_id']) ? (int)$data['fiscal_year_id'] : null;
      $branchId = !empty($data['branch_id']) ? (int)$data['branch_id'] : null;

      if ($amount <= 0) {
         ResponseHelper::error('Expense amount must be greater than zero', 400);
      }

      if ($expenseDate > date('Y-m-d')) {
         ResponseHelper::error('Expense date cannot be in the future', 400);
      }

      // Validate category
      $valid = $orm->runQuery(
         "SELECT COUNT(*) AS cat_ok FROM expense_category WHERE ExpCategoryID = :cat AND IsActive = 1",
         [':cat' => $categoryId]
      )[0];

      if ($valid['cat_ok'] == 0) {
         ResponseHelper::error('Invalid expense category', 400);
      }

      // Validate fiscal year if provided
      if ($fiscalYearId !== null) {
         $fyValid = $orm->runQuery(
            "SELECT COUNT(*) AS fy_ok FROM fiscal_year WHERE FiscalYearID = :fy AND Status = 'Active'",
            [':fy' => $fiscalYearId]
         )[0];
         if ($fyValid['fy_ok'] == 0) {
            ResponseHelper::error('Invalid or inactive fiscal year', 400);
         }
      }

      // Validate branch if provided
      if ($branchId) {
         $branchValid = $orm->runQuery(
            "SELECT COUNT(*) AS ok FROM branch WHERE BranchID = :br AND IsActive = 1",
            [':br' => $branchId]
         )[0];
         if ($branchValid['ok'] == 0) {
            ResponseHelper::error('Invalid branch', 400);
         }
      }

      $currentUserId = Auth::getCurrentUserId();

      $expenseId = $orm->insert('expense', [
         'ExpTitle'      => $data['title'],
         'ExpPurpose'    => $data['purpose'] ?? null,
         'ExpAmount'     => $amount,
         'ExpDate'       => $expenseDate,
         'ExpCategoryID' => $categoryId,
         'FiscalYearID'  => $fiscalYearId,
         'BranchID'      => $branchId,
         'ExpStatus'     => self::STATUS_PENDING,
         'MbrID'         => $currentUserId,
         'RequestedBy'   => $currentUserId,
         'RequestedAt'   => date('Y-m-d H:i:s')
      ])['id'];

      Helpers::logError("New expense request: ExpID $expenseId | Amount $amount");
      return ['status' => 'success', 'expense_id' => $expenseId];
   }

   /**
    * Update an existing expense (only pending expenses can be updated)
    */
   public static function update(int $expenseId, array $data): array
   {
      $orm = new ORM();

      $expense = $orm->getWhere('expense', ['ExpID' => $expenseId])[0] ?? null;
      if (!$expense) {
         ResponseHelper::error('Expense not found', 404);
      }

      if ($expense['ExpStatus'] !== self::STATUS_PENDING) {
         ResponseHelper::error('Only pending expenses can be updated', 400);
      }

      Helpers::validateInput($data, [
         'title'          => 'max:100|nullable',
         'amount'         => 'numeric|nullable',
         'expense_date'   => 'date|nullable',
         'category_id'    => 'numeric|nullable',
         'fiscal_year_id' => 'numeric|nullable',
         'purpose'        => 'max:1000|nullable',
      ]);

      $update = [];

      if (!empty($data['title'])) {
         $update['ExpTitle'] = $data['title'];
      }
      if (isset($data['amount']) && (float)$data['amount'] > 0) {
         $update['ExpAmount'] = (float)$data['amount'];
      }
      if (!empty($data['expense_date'])) {
         if ($data['expense_date'] > date('Y-m-d')) {
            ResponseHelper::error('Expense date cannot be in the future', 400);
         }
         $update['ExpDate'] = $data['expense_date'];
      }
      if (!empty($data['category_id'])) {
         $update['ExpCategoryID'] = (int)$data['category_id'];
      }
      if (isset($data['fiscal_year_id'])) {
         $update['FiscalYearID'] = !empty($data['fiscal_year_id']) ? (int)$data['fiscal_year_id'] : null;
      }
      if (isset($data['purpose'])) {
         $update['ExpPurpose'] = $data['purpose'];
      }
      if (!empty($data['branch_id'])) {
         $update['BranchID'] = (int)$data['branch_id'];
      }

      if (!empty($update)) {
         $update['UpdatedBy'] = Auth::getCurrentUserId();
         $update['UpdatedAt'] = date('Y-m-d H:i:s');
         $orm->update('expense', $update, ['ExpID' => $expenseId]);
      }

      return ['status' => 'success', 'expense_id' => $expenseId];
   }

   /**
    * Soft delete an expense
    */
   public static function delete(int $expenseId): array
   {
      $orm = new ORM();

      $expense = $orm->getWhere('expense', ['ExpID' => $expenseId])[0] ?? null;
      if (!$expense) {
         ResponseHelper::error('Expense not found', 404);
      }

      $affected = $orm->update('expense', [
         'Deleted' => 1,
         'DeletedBy' => Auth::getCurrentUserId(),
         'DeletedAt' => date('Y-m-d H:i:s')
      ], ['ExpID' => $expenseId]);

      if ($affected === 0) {
         ResponseHelper::error('Failed to delete expense', 500);
      }

      Helpers::logError("Expense soft-deleted: ExpID $expenseId by " . Auth::getCurrentUserId());
      return ['status' => 'success'];
   }

   /**
    * Review (approve or decline) an expense
    */
   public static function review(int $expenseId, string $action, ?string $remarks = null): array
   {
      $orm = new ORM();

      $expense = $orm->getWhere('expense', ['ExpID' => $expenseId])[0] ?? null;
      if (!$expense) {
         ResponseHelper::error('Expense not found', 404);
      }

      if ($expense['ExpStatus'] !== self::STATUS_PENDING) {
         ResponseHelper::error('Only pending expenses can be reviewed', 400);
      }

      if (!in_array($action, ['approve', 'reject'], true)) {
         ResponseHelper::error('Action must be approve or reject', 400);
      }

      $newStatus = $action === 'approve' ? self::STATUS_APPROVED : self::STATUS_DECLINED;
      $approvalStatus = $action === 'approve' ? 'Approved' : 'Declined';

      $orm->beginTransaction();
      try {
         // Update expense status
         $orm->update('expense', ['ExpStatus' => $newStatus], ['ExpID' => $expenseId]);

         // Insert approval record
         $orm->insert('expense_approval', [
            'ExpID'          => $expenseId,
            'ApproverID'     => Auth::getCurrentUserId(),
            'ApprovalStatus' => $approvalStatus,
            'Comments'       => $remarks,
            'ApprovalDate'   => date('Y-m-d H:i:s')
         ]);

         $orm->commit();
      } catch (Exception $e) {
         $orm->rollBack();
         throw $e;
      }

      Helpers::logError("Expense {$action}d: ExpID $expenseId");
      return ['status' => 'success', 'message' => "Expense has been {$action}d"];
   }

   /**
    * Retrieve a single expense with full details
    */
   public static function get(int $expenseId): array
   {
      $orm = new ORM();

      $result = $orm->runQuery(
         "SELECT e.*, 
                 ec.ExpCategoryName AS CategoryName,
                 fy.FiscalYearName,
                 b.BranchName,
                 r.MbrFirstName AS RequesterFirstName,
                 r.MbrFamilyName AS RequesterFamilyName,
                 ea.ApproverID,
                 ea.ApprovalStatus,
                 ea.Comments AS ApprovalRemarks,
                 ea.ApprovalDate AS ApprovedAt,
                 a.MbrFirstName AS ApproverFirstName,
                 a.MbrFamilyName AS ApproverFamilyName
          FROM expense e
          JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
          LEFT JOIN fiscal_year fy ON e.FiscalYearID = fy.FiscalYearID
          LEFT JOIN branch b ON e.BranchID = b.BranchID
          LEFT JOIN churchmember r ON e.RequestedBy = r.MbrID
          LEFT JOIN expense_approval ea ON e.ExpID = ea.ExpID
          LEFT JOIN churchmember a ON ea.ApproverID = a.MbrID
          WHERE e.ExpID = :id",
         [':id' => $expenseId]
      );

      if (empty($result)) {
         ResponseHelper::error('Expense not found', 404);
      }

      // Map to expected field names for frontend compatibility
      $expense = $result[0];
      return [
         'ExpenseID' => $expense['ExpID'],
         'ExpenseTitle' => $expense['ExpTitle'],
         'ExpensePurpose' => $expense['ExpPurpose'],
         'ExpenseAmount' => $expense['ExpAmount'],
         'ExpenseDate' => $expense['ExpDate'],
         'ExpenseStatus' => $expense['ExpStatus'],
         'ExpenseCategoryID' => $expense['ExpCategoryID'],
         'FiscalYearID' => $expense['FiscalYearID'],
         'BranchID' => $expense['BranchID'],
         'ProofFile' => $expense['ProofFile'] ?? null,
         'CategoryName' => $expense['CategoryName'],
         'FiscalYearName' => $expense['FiscalYearName'],
         'BranchName' => $expense['BranchName'],
         'RequesterFirstName' => $expense['RequesterFirstName'],
         'RequesterFamilyName' => $expense['RequesterFamilyName'],
         'RequestedAt' => $expense['RequestedAt'],
         'ApproverFirstName' => $expense['ApproverFirstName'],
         'ApproverFamilyName' => $expense['ApproverFamilyName'],
         'ApprovedAt' => $expense['ApprovedAt'],
         'ApprovalRemarks' => $expense['ApprovalRemarks']
      ];
   }

   /**
    * Retrieve paginated list of expenses with filters
    */
   public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
   {
      $orm = new ORM();
      $offset = ($page - 1) * $limit;

      $where = ['e.Deleted = 0'];
      $params = [];

      if (!empty($filters['fiscal_year_id'])) {
         $where[] = 'e.FiscalYearID = :fy';
         $params[':fy'] = (int)$filters['fiscal_year_id'];
      }
      if (!empty($filters['branch_id'])) {
         $where[] = 'e.BranchID = :br';
         $params[':br'] = (int)$filters['branch_id'];
      }
      if (!empty($filters['category_id'])) {
         $where[] = 'e.ExpCategoryID = :cat';
         $params[':cat'] = (int)$filters['category_id'];
      }
      if (!empty($filters['status'])) {
         $where[] = 'e.ExpStatus = :status';
         $params[':status'] = $filters['status'];
      }
      if (!empty($filters['start_date'])) {
         $where[] = 'e.ExpDate >= :start';
         $params[':start'] = $filters['start_date'];
      }
      if (!empty($filters['end_date'])) {
         $where[] = 'e.ExpDate <= :end';
         $params[':end'] = $filters['end_date'];
      }

      $whereClause = 'WHERE ' . implode(' AND ', $where);

      // Sorting
      $orderBy = 'e.ExpDate DESC';
      if (!empty($filters['sort_by'])) {
         $columnMap = [
            'ExpenseTitle' => 'e.ExpTitle',
            'ExpenseAmount' => 'e.ExpAmount',
            'ExpenseDate' => 'e.ExpDate',
            'CategoryName' => 'ec.ExpCategoryName',
            'BranchName' => 'b.BranchName',
            'ExpenseStatus' => 'e.ExpStatus'
         ];
         $sortCol = $columnMap[$filters['sort_by']] ?? 'e.ExpDate';
         $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
         $orderBy = "$sortCol $sortDir";
      }

      $expenses = $orm->runQuery(
         "SELECT e.ExpID, e.ExpTitle, e.ExpPurpose, e.ExpAmount, e.ExpDate, e.ExpStatus,
                 e.ExpCategoryID, e.FiscalYearID, e.BranchID, e.ProofFile,
                 ec.ExpCategoryName, fy.FiscalYearName, b.BranchName
          FROM expense e
          JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
          LEFT JOIN fiscal_year fy ON e.FiscalYearID = fy.FiscalYearID
          LEFT JOIN branch b ON e.BranchID = b.BranchID
          $whereClause
          ORDER BY $orderBy
          LIMIT :limit OFFSET :offset",
         array_merge($params, [':limit' => $limit, ':offset' => $offset])
      );

      // Map to expected field names
      $mapped = array_map(function ($e) {
         return [
            'ExpenseID' => $e['ExpID'],
            'ExpenseTitle' => $e['ExpTitle'],
            'ExpensePurpose' => $e['ExpPurpose'],
            'ExpenseAmount' => $e['ExpAmount'],
            'ExpenseDate' => $e['ExpDate'],
            'ExpenseStatus' => $e['ExpStatus'],
            'CategoryName' => $e['ExpCategoryName'],
            'FiscalYearName' => $e['FiscalYearName'],
            'BranchName' => $e['BranchName'],
            'ProofFile' => $e['ProofFile'] ?? null
         ];
      }, $expenses);

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total FROM expense e $whereClause",
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
    * Upload proof file for an expense (only for approved expenses)
    */
   public static function uploadProof(int $expenseId, array $file): array
   {
      $orm = new ORM();

      $expense = $orm->getWhere('expense', ['ExpID' => $expenseId])[0] ?? null;
      if (!$expense) {
         ResponseHelper::error('Expense not found', 404);
      }

      // Only allow proof upload for approved expenses
      if ($expense['ExpStatus'] !== self::STATUS_APPROVED) {
         ResponseHelper::error('Proof can only be uploaded for approved expenses', 400);
      }

      // Validate file
      $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
      $maxSize = 5 * 1024 * 1024; // 5MB

      if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
         ResponseHelper::error('No file uploaded', 400);
      }

      if ($file['size'] > $maxSize) {
         ResponseHelper::error('File size exceeds 5MB limit', 400);
      }

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($finfo, $file['tmp_name']);
      finfo_close($finfo);

      if (!in_array($mimeType, $allowedTypes)) {
         ResponseHelper::error('Invalid file type. Allowed: JPG, PNG, GIF, PDF', 400);
      }

      // Create upload directory
      $uploadDir = __DIR__ . '/../public/uploads/expenses/' . date('Y/m');
      if (!is_dir($uploadDir)) {
         mkdir($uploadDir, 0755, true);
      }

      // Generate unique filename
      $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = 'expense_' . $expenseId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
      $filepath = $uploadDir . '/' . $filename;
      $relativePath = 'uploads/expenses/' . date('Y/m') . '/' . $filename;

      // Delete old proof file if exists
      if (!empty($expense['ProofFile']) && file_exists(__DIR__ . '/../' . $expense['ProofFile'])) {
         unlink(__DIR__ . '/../' . $expense['ProofFile']);
      }

      // Move uploaded file
      if (!move_uploaded_file($file['tmp_name'], $filepath)) {
         ResponseHelper::error('Failed to save file', 500);
      }

      // Update database
      $orm->update('expense', ['ProofFile' => $relativePath], ['ExpID' => $expenseId]);

      Helpers::logError("Proof file uploaded for expense $expenseId: $relativePath");

      return [
         'status' => 'success',
         'proof_file' => $relativePath
      ];
   }

   /**
    * Delete proof file for an expense
    */
   public static function deleteProof(int $expenseId): array
   {
      $orm = new ORM();

      $expense = $orm->getWhere('expense', ['ExpID' => $expenseId])[0] ?? null;
      if (!$expense) {
         ResponseHelper::error('Expense not found', 404);
      }

      if (empty($expense['ProofFile'])) {
         ResponseHelper::error('No proof file to delete', 400);
      }

      // Delete file
      $filepath = __DIR__ . '/../' . $expense['ProofFile'];
      if (file_exists($filepath)) {
         unlink($filepath);
      }

      // Update database
      $orm->update('expense', ['ProofFile' => null], ['ExpID' => $expenseId]);

      return ['status' => 'success'];
   }

   /**
    * Cancel a pending expense
    */
   public static function cancel(int $expenseId, string $reason): array
   {
      $orm = new ORM();

      $expense = $orm->getWhere('expense', ['ExpID' => $expenseId])[0] ?? null;
      if (!$expense) {
         ResponseHelper::error('Expense not found', 404);
      }

      if ($expense['ExpStatus'] !== self::STATUS_PENDING) {
         ResponseHelper::error('Only pending expenses can be cancelled', 400);
      }

      $orm->beginTransaction();
      try {
         // Update expense status to Declined
         $orm->update('expense', ['ExpStatus' => self::STATUS_DECLINED], ['ExpID' => $expenseId]);

         // Insert cancellation record in approval table
         $orm->insert('expense_approval', [
            'ExpID'          => $expenseId,
            'ApproverID'     => Auth::getCurrentUserId(),
            'ApprovalStatus' => 'Declined',
            'Comments'       => 'Cancelled: ' . $reason,
            'ApprovalDate'   => date('Y-m-d H:i:s')
         ]);

         $orm->commit();
      } catch (Exception $e) {
         $orm->rollBack();
         throw $e;
      }

      Helpers::logError("Expense cancelled: ExpID $expenseId | Reason: $reason");
      return ['status' => 'success', 'message' => 'Expense has been cancelled'];
   }
}
