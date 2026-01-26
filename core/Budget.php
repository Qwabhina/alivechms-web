<?php

/**
 * Budget Management
 *
 * Complete budget lifecycle with line items, draft workflow,
 * submission, approval/rejection, total recalculation, and audit trail.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class Budget
{
   private const STATUS_DRAFT     = 'Draft';
   private const STATUS_SUBMITTED = 'Submitted';
   private const STATUS_APPROVED  = 'Approved';
   private const STATUS_REJECTED  = 'Rejected';

   /**
    * Create a new budget with line items
    *
    * @param array $data Budget payload including items array
    * @return array ['status' => 'success', 'budget_id' => int]
    * @throws Exception On validation or database failure
    */
   public static function create(array $data): array
   {
      $orm = new ORM();

      Helpers::validateInput($data, [
         'fiscal_year_id' => 'required|numeric',
         'branch_id'      => 'required|numeric',
         'title'          => 'required|max:150',
         'description'    => 'max:500|nullable',
         'items'          => 'required'
      ]);

      if (!is_array($data['items']) || empty($data['items'])) ResponseHelper::error('At least one budget item is required', 400);

      $fiscalYearId = (int)$data['fiscal_year_id'];
      $branchId     = (int)$data['branch_id'];

      // Validate references
      if (empty($orm->getWhere('fiscal_year', ['FiscalYearID' => $fiscalYearId, 'Status' => 'Active']))) ResponseHelper::error('Invalid or inactive fiscal year', 400);
      if (empty($orm->getWhere('branch', ['BranchID' => $branchId, 'IsActive' => 1]))) ResponseHelper::error('Invalid branch', 400);

      $orm->beginTransaction();
      try {
         $budgetId = $orm->insert('churchbudget', [
            'FiscalYearID'      => $fiscalYearId,
            'BranchID'          => $branchId,
            'BudgetTitle'       => $data['title'],
            'BudgetDescription' => $data['description'] ?? null,
            'BudgetStatus'      => self::STATUS_DRAFT,
            'CreatedBy'         => Auth::getCurrentUserId(),
            'CreatedAt'         => date('Y-m-d H:i:s'),
            'TotalAmount'       => 0.0
         ])['id'];

         $total = self::saveItemsAndRecalculate($budgetId, $data['items'], $orm);

         $orm->update('churchbudget', ['TotalAmount' => $total], ['BudgetID' => $budgetId]);
         $orm->commit();

         // Return success response
         return ['status' => 'success', 'budget_id' => $budgetId];
      } catch (Exception $e) {
         $orm->rollBack();
         Helpers::logError("Budget creation failed: " . $e->getMessage());
         throw $e;
      }
   }

   /**
    * Update budget title/description (only in Draft state)
    *
    * @param int   $budgetId Budget ID
    * @param array $data     Updated data
    * @return array Success response
    */
   public static function update(int $budgetId, array $data): array
   {
      $orm = new ORM();
      self::ensureDraft($budgetId);

      $update = [];
      if (!empty($data['title']))       $update['BudgetTitle']       = $data['title'];
      if (isset($data['description']))  $update['BudgetDescription'] = $data['description'];

      if (!empty($update)) {
         $update['UpdatedAt'] = date('Y-m-d H:i:s');
         if (!empty($_SESSION['user_id'])) {
            $update['UpdatedBy'] = (int)$_SESSION['user_id'];
         }
         $orm->update('churchbudget', $update, ['BudgetID' => $budgetId]);
      }

      return ['status' => 'success'];
   }

   /**
    * Submit budget for approval
    *
    * @param int $budgetId Budget ID
    * @return array Success response
    */
   public static function submitForApproval(int $budgetId): array
   {
      $orm = new ORM();
      // Fetch budget to validate state and constraints
      $budget = $orm->getWhere('churchbudget', ['BudgetID' => $budgetId, 'Deleted' => 0])[0] ?? null;

      if (!$budget) {
         ResponseHelper::error('Budget not found', 404);
      }

      if ($budget['BudgetStatus'] !== self::STATUS_DRAFT) {
         ResponseHelper::error('Only draft budgets can be submitted', 400);
      }

      if ((float)$budget['TotalAmount'] <= 0) {
         ResponseHelper::error('Cannot submit a budget with zero total amount', 400);
      }

      // Check Fiscal Year status
      $fy = $orm->getWhere('fiscal_year', ['FiscalYearID' => $budget['FiscalYearID']])[0] ?? null;
      if (!$fy || $fy['Status'] !== 'Active') {
         ResponseHelper::error('Cannot submit budget for an inactive fiscal year', 400);
      }

      $orm->update('churchbudget', [
         'BudgetStatus'  => self::STATUS_SUBMITTED,
         'SubmittedAt'   => date('Y-m-d H:i:s'),
         'SubmittedBy'   => Auth::getCurrentUserId()
      ], ['BudgetID' => $budgetId]);

      Helpers::logError("Budget submitted: BudgetID $budgetId");
      return ['status' => 'success'];
   }

   /**
    * Review submitted budget (approve/reject)
    *
    * @param int         $budgetId Budget ID
    * @param string      $action   'approve' or 'reject'
    * @param string|null $remarks  Optional remarks
    * @return array Success response
    */
   public static function review(int $budgetId, string $action, ?string $remarks = null): array
   {
      $orm = new ORM();

      $budget = $orm->getWhere('churchbudget', ['BudgetID' => $budgetId, 'Deleted' => 0])[0] ?? null;
      if (!$budget || $budget['BudgetStatus'] !== self::STATUS_SUBMITTED) {
         ResponseHelper::error('Only submitted budgets can be reviewed', 400);
      }

      $newStatus = $action === 'approve' ? self::STATUS_APPROVED : self::STATUS_REJECTED;

      $orm->update('churchbudget', [
         'BudgetStatus'    => $newStatus,
         'ApprovedBy'      => Auth::getCurrentUserId(),
         'ApprovedAt'      => date('Y-m-d H:i:s'),
         'ApprovalRemarks' => $remarks
      ], ['BudgetID' => $budgetId]);

      return ['status' => 'success', 'message' => "Budget has been {$action}d"];
   }

   /**
    * Retrieve a single budget with items and metadata
    *
    * @param int $budgetId Budget ID
    * @return array Full budget data
    */
   public static function get(int $budgetId): array
   {
      $orm = new ORM();

      $result = $orm->selectWithJoin(
         baseTable: 'churchbudget b',
         joins: [
            ['table' => 'fiscal_year f',  'on' => 'b.FiscalYearID = f.FiscalYearID'],
            ['table' => 'branch br',      'on' => 'b.BranchID = br.BranchID'],
            ['table' => 'churchmember c', 'on' => 'b.CreatedBy = c.MbrID', 'type' => 'LEFT'],
            ['table' => 'churchmember a', 'on' => 'b.ApprovedBy = a.MbrID', 'type' => 'LEFT']
         ],
         fields: [
            'b.*',
            'f.FiscalYearName AS FiscalYear',
            'br.BranchName',
            'c.MbrFirstName AS CreatorFirstName',
            'c.MbrFamilyName AS CreatorFamilyName',
            'a.MbrFirstName AS ApproverFirstName',
            'a.MbrFamilyName AS ApproverFamilyName'
         ],
         conditions: ['b.BudgetID' => ':id', 'b.Deleted' => 0],
         params: [':id' => $budgetId]
      );

      if (empty($result)) {
         ResponseHelper::error('Budget not found', 404);
      }

      $items = $orm->getWhere('budget_items', ['BudgetID' => $budgetId]);
      $budget = $result[0];
      $budget['items'] = $items;

      return $budget;
   }

   /**
    * Retrieve paginated budgets with filters
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

      $conditions = ['b.Deleted' => 0];
      $params     = [];

      if (!empty($filters['fiscal_year_id'])) {
         $conditions['b.FiscalYearID'] = ':fy';
         $params[':fy'] = (int)$filters['fiscal_year_id'];
      }
      if (!empty($filters['branch_id'])) {
         $conditions['b.BranchID'] = ':br';
         $params[':br'] = (int)$filters['branch_id'];
      }
      if (!empty($filters['status'])) {
         $conditions['b.BudgetStatus'] = ':st';
         $params[':st'] = $filters['status'];
      }

      $budgets = $orm->selectWithJoin(
         baseTable: 'churchbudget b',
         joins: [
            ['table' => 'fiscal_year f', 'on' => 'b.FiscalYearID = f.FiscalYearID'],
            ['table' => 'branch br',     'on' => 'b.BranchID = br.BranchID']
         ],
         fields: [
            'b.BudgetID',
            'b.BudgetTitle',
            'b.TotalAmount',
            'b.BudgetStatus',
            'b.CreatedAt',
            'f.FiscalYearName',
            'br.BranchName'
         ],
         conditions: $conditions,
         params: $params,
         orderBy: ['b.CreatedAt' => 'DESC'],
         limit: $limit,
         offset: $offset
      );

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total FROM churchbudget b WHERE b.Deleted = 0" .
            (count($conditions) > 1 ? " AND " . implode(' AND ', array_slice(array_keys($conditions), 1)) : ''),
         $params
      )[0]['total'];

      return [
         'data' => $budgets,
         'pagination' => [
            'page'   => $page,
            'limit'  => $limit,
            'total'  => (int)$total,
            'pages'  => (int)ceil($total / $limit)
         ]
      ];
   }

   /** Budget Item Management */

   public static function addItem(int $budgetId, array $item): array
   {
      $orm = new ORM();
      self::ensureDraft($budgetId);

      Helpers::validateInput($item, [
         'category'    => 'required|max:100',
         'description' => 'max:300|nullable',
         'amount'      => 'required|numeric'
      ]);

      $amount = (float)$item['amount'];
      if ($amount <= 0) {
         ResponseHelper::error('Amount must be greater than zero', 400);
      }

      $orm->beginTransaction();
      try {
         $orm->insert('budget_items', [
            'BudgetID'    => $budgetId,
            'Category'    => $item['category'],
            'Description' => $item['description'] ?? null,
            'Amount'      => $amount
         ]);

         self::recalculateTotal($budgetId, $orm);
         $orm->commit();
         return ['status' => 'success', 'message' => 'Item added'];
      } catch (Exception $e) {
         $orm->rollBack();
         throw $e;
      }
   }

   public static function updateItem(int $itemId, array $data): array
   {
      $orm = new ORM();

      $item = $orm->getWhere('budget_items', ['ItemID' => $itemId])[0] ?? null;
      if (!$item) {
         ResponseHelper::error('Item not found', 404);
      }

      self::ensureDraft((int)$item['BudgetID']);

      $update = [];
      if (!empty($data['category']))    $update['Category']    = $data['category'];
      if (isset($data['description']))  $update['Description'] = $data['description'];
      if (isset($data['amount'])) {
         $amount = (float)$data['amount'];
         if ($amount <= 0) {
            ResponseHelper::error('Amount must be greater than zero', 400);
         }
         $update['Amount'] = $amount;
      }

      if (empty($update)) {
         return ['status' => 'success', 'message' => 'No changes provided'];
      }

      $orm->beginTransaction();
      try {
         $orm->update('budget_items', $update, ['ItemID' => $itemId]);
         self::recalculateTotal((int)$item['BudgetID'], $orm);
         $orm->commit();
         return ['status' => 'success', 'message' => 'Item updated'];
      } catch (Exception $e) {
         $orm->rollBack();
         throw $e;
      }
   }

   public static function deleteItem(int $itemId): array
   {
      $orm = new ORM();

      $item = $orm->getWhere('budget_items', ['ItemID' => $itemId])[0] ?? null;
      if (!$item) {
         ResponseHelper::error('Item not found', 404);
      }

      self::ensureDraft((int)$item['BudgetID']);

      $orm->beginTransaction();
      try {
         $orm->delete('budget_items', ['ItemID' => $itemId]);
         self::recalculateTotal((int)$item['BudgetID'], $orm);
         $orm->commit();
         return ['status' => 'success', 'message' => 'Item deleted'];
      } catch (Exception $e) {
         $orm->rollBack();
         throw $e;
      }
   }

   /** Private Helpers */

   private static function ensureDraft(int $budgetId): void
   {
      $orm = new ORM();
      $b   = $orm->getWhere('churchbudget', ['BudgetID' => $budgetId, 'Deleted' => 0])[0] ?? null;
      if (!$b) {
         ResponseHelper::error('Budget not found', 404);
      }
      if ($b['BudgetStatus'] !== self::STATUS_DRAFT) {
         ResponseHelper::error('Only draft budgets can be modified', 400);
      }
   }

   private static function saveItemsAndRecalculate(int $budgetId, array $items, ORM $orm): float
   {
      $total = 0.0;
      foreach ($items as $item) {
         $amount = (float)($item['amount'] ?? 0);
         if ($amount <= 0) continue;

         $orm->insert('budget_items', [
            'BudgetID'    => $budgetId,
            'Category'    => $item['category'] ?? '',
            'Description' => $item['description'] ?? null,
            'Amount'      => $amount
         ]);
         $total += $amount;
      }
      return $total;
   }

   private static function recalculateTotal(int $budgetId, ORM $orm): void
   {
      $items = $orm->getWhere('budget_items', ['BudgetID' => $budgetId]);
      $total = array_sum(array_column($items, 'Amount'));

      $orm->update('churchbudget', ['TotalAmount' => $total], ['BudgetID' => $budgetId]);
   }
}