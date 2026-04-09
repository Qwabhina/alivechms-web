<?php

/**
 * Budget Management Service
 *
 * Orchestrates budget workflows and delegates data persistence to BudgetRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\Financial\BudgetRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use Exception;

class Budget
{
   private const STATUS_DRAFT     = 'Draft';
   private const STATUS_SUBMITTED = 'Submitted';
   private const STATUS_APPROVED  = 'Approved';
   private const STATUS_REJECTED  = 'Rejected';

   /**
    * Create a new budget with line items
    */
   public static function create(array $data): array
   {
      $repo = new BudgetRepository();

      Helpers::validateInput($data, [
         'fiscal_year_id' => 'required|numeric',
         'branch_id'      => 'required|numeric',
         'title'          => 'required|max:150',
         'description'    => 'max:500|nullable',
         'items'          => 'required'
      ]);

      if (!is_array($data['items']) || empty($data['items'])) {
         ResponseHelper::error('At least one budget item is required', 400);
      }

      $fiscalYearId = (int)$data['fiscal_year_id'];
      $branchId     = (int)$data['branch_id'];

      if (!$repo->isValidFiscalYear($fiscalYearId))
         ResponseHelper::error('Invalid or inactive fiscal year', 400);
      if (!$repo->isValidBranch($branchId))
         ResponseHelper::error('Invalid branch', 400);

      $repo->beginTransaction();
      try {
         $budgetId = $repo->create([
            'FiscalYearID'      => $fiscalYearId,
            'BranchID'          => $branchId,
            'BudgetTitle'       => $data['title'],
            'BudgetSummary' => $data['description'] ?? null, // Schema has BudgetSummary, old code used BudgetDescription
            'BudgetStatus'      => self::STATUS_DRAFT,
            'CreatedBy'         => Auth::getCurrentUserId(),
            'CreatedAt'         => date('Y-m-d H:i:s'),
            'TotalAmount'       => 0.0
         ]);

         foreach ($data['items'] as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            if ($amount <= 0)
               continue;

            $repo->insertItem([
               'BudgetID' => $budgetId,
               'ItemName' => $item['category'] ?? '', // Schema has ItemName, old code used Category
               'Amount' => $amount,
               'CategoryType' => $item['type'] ?? 'Expense', // Required in schema
               'SubcategoryID' => (int) ($item['subcategory_id'] ?? 1) // Required in schema
            ]);
         }

         $repo->recalculateTotal($budgetId);
         $repo->commit();

         return ['status' => 'success', 'budget_id' => $budgetId];
      } catch (Exception $e) {
         $repo->rollBack();
         Helpers::logError("Budget creation failed: " . $e->getMessage());
         throw $e;
      }
   }

   /**
    * Update budget title/description (only in Draft state)
    */
   public static function update(int $budgetId, array $data): array
   {
      $repo = new BudgetRepository();
      self::ensureDraft($budgetId, $repo);

      $update = [];
      if (!empty($data['title']))
         $update['BudgetTitle'] = $data['title'];
      if (isset($data['description']))
         $update['BudgetSummary'] = $data['description'];

      if (!empty($update)) {
         $update['UpdatedAt'] = date('Y-m-d H:i:s');
         $repo->update($budgetId, $update);
      }

      return ['status' => 'success'];
   }

   /**
    * Submit budget for approval
    */
   public static function submitForApproval(int $budgetId): array
   {
      $repo = new BudgetRepository();
      self::ensureDraft($budgetId, $repo);

      $repo->update($budgetId, [
         'BudgetStatus' => self::STATUS_SUBMITTED,
         'UpdatedAt' => date('Y-m-d H:i:s')
      ]);

      Helpers::logError("Budget submitted: BudgetID $budgetId");
      return ['status' => 'success'];
   }

   /**
    * Review submitted budget
    */
   public static function review(int $budgetId, string $action, ?string $remarks = null): array
   {
      $repo = new BudgetRepository();
      $budget = $repo->findById($budgetId);

      if (!$budget || $budget['BudgetStatus'] !== self::STATUS_SUBMITTED) {
         ResponseHelper::error('Only submitted budgets can be reviewed', 400);
      }

      $newStatus = $action === 'approve' ? self::STATUS_APPROVED : self::STATUS_REJECTED;

      $repo->update($budgetId, [
         'BudgetStatus' => $newStatus,
         'UpdatedAt' => date('Y-m-d H:i:s')
         // Note: Approval details usually go into budget_approval table
      ]);

      return ['status' => 'success', 'message' => "Budget has been {$action}d"];
   }

   /**
    * Retrieve a single budget with items
    */
   public static function get(int $budgetId): array
   {
      $repo = new BudgetRepository();
      $budget = $repo->findById($budgetId);

      if (!$budget) {
         ResponseHelper::error('Budget not found', 404);
      }

      $budget['items'] = $repo->getItems($budgetId);
      return $budget;
   }

   /**
    * Paginated budgets
    */
   public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
   {
      $repo = new BudgetRepository();
      $offset = ($page - 1) * $limit;
      $result = $repo->findAll($limit, $offset, $filters);

      return [
         'data' => $result['data'],
         'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
            'pages' => (int) ceil($result['total'] / $limit)
         ]
      ];
   }

   /**
    * Add member to item
    */
   public static function addItem(int $budgetId, array $item): array
   {
      $repo = new BudgetRepository();
      self::ensureDraft($budgetId, $repo);

      Helpers::validateInput($item, [
         'category'    => 'required|max:100',
         'amount' => 'required|numeric',
         'type' => 'in:Income,Expense',
         'subcategory_id' => 'required|numeric'
      ]);

      $repo->beginTransaction();
      try {
         $repo->insertItem([
            'BudgetID' => $budgetId,
            'ItemName' => $item['category'],
            'Amount' => (float) $item['amount'],
            'CategoryType' => $item['type'] ?? 'Expense',
            'SubcategoryID' => (int) $item['subcategory_id']
         ]);

         $repo->recalculateTotal($budgetId);
         $repo->commit();
         return ['status' => 'success', 'message' => 'Item added'];
      } catch (Exception $e) {
         $repo->rollBack();
         throw $e;
      }
   }

   private static function ensureDraft(int $budgetId, BudgetRepository $repo): void
   {
      $b = $repo->findById($budgetId);
      if (!$b)
         ResponseHelper::error('Budget not found', 404);
      if ($b['BudgetStatus'] !== self::STATUS_DRAFT) {
         ResponseHelper::error('Only draft budgets can be modified', 400);
      }
   }

   public static function deleteItem(int $budgetId, int $itemId): array
   {
      $repo = new BudgetRepository();
      self::ensureDraft($budgetId, $repo);

      $repo->deleteItem($itemId);
      return ['status' => 'success', 'message' => 'Item deleted'];
   }

   public static function updateItem(int $budgetId, int $itemId, array $item): array
   {
      $repo = new BudgetRepository();
      self::ensureDraft($budgetId, $repo);

      Helpers::validateInput($item, [
         'category' => 'required|max:100',
         'amount' => 'required|numeric',
         'type' => 'in:Income,Expense',
         'subcategory_id' => 'required|numeric'
      ]);

      $repo->updateItem($itemId, [
         'ItemName' => $item['category'],
         'Amount' => (float) $item['amount'],
         'CategoryType' => $item['type'] ?? 'Expense',
         'SubcategoryID' => (int) $item['subcategory_id']
      ]);
      return ['status' => 'success', 'message' => 'Item updated'];
   }

}