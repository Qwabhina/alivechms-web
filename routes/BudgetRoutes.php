<?php

/**
 * Budget API Routes
 *
 * Full budget lifecycle with line-item management and approval workflow:
 * - Create draft budget with items
 * - Update draft budget
 * - Submit for approval
 * - Review (approve/reject)
 * - View single budget with items
 * - Paginated listing with filters
 * - Line-item CRUD (add/update/delete)
 *
 * All operations strictly permission-controlled.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Budget.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class BudgetRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE BUDGET (with items)
         $method === 'POST' && $path === 'budget/create' => (function () {
            self::authenticate();
            self::authorize('create_budgets');

            $payload = self::getPayload();

            $result = Budget::create($payload);
            ResponseHelper::created($result, 'Budget created');
         })(),

         // UPDATE BUDGET (title/description only, draft state)
         $method === 'PUT' && $pathParts[0] === 'budget' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('edit_budgets');

            $budgetId = self::getIdFromPath($pathParts, 2, 'Budget ID');

            $payload = self::getPayload();

            $result = Budget::update($budgetId, $payload);
            ResponseHelper::success($result, 'Budget updated');
         })(),

         // SUBMIT BUDGET FOR APPROVAL
         $method === 'PUT' && $pathParts[0] === 'budget' && ($pathParts[1] ?? '') === 'submit' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('submit_budgets');

            $budgetId = self::getIdFromPath($pathParts, 2, 'Budget ID');

            $result = Budget::submitForApproval($budgetId);
            ResponseHelper::success($result, 'Budget submitted for approval');
         })(),

         // REVIEW BUDGET (Approve/Reject)
         $method === 'POST' && $pathParts[0] === 'budget' && ($pathParts[1] ?? '') === 'review' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('approve_budgets');

            $budgetId = self::getIdFromPath($pathParts, 2, 'Budget ID');

            $payload = self::getPayload([
               'action' => 'required|in:approve,reject',
               'remarks' => 'nullable|max:500'
            ]);

            $result = Budget::review(
               $budgetId,
               $payload['action'],
               $payload['remarks'] ?? null
            );
            ResponseHelper::success($result, 'Budget reviewed');
         })(),

         // VIEW SINGLE BUDGET (with items)
         $method === 'GET' && $pathParts[0] === 'budget' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_budgets');

            $budgetId = self::getIdFromPath($pathParts, 2, 'Budget ID');

            $budget = Budget::get($budgetId);
            ResponseHelper::success($budget);
         })(),

         // LIST ALL BUDGETS (Paginated + Filtered)
         $method === 'GET' && $path === 'budget/all' => (function () {
            self::authenticate();
            self::authorize('view_budgets');

            [$page, $limit] = self::getPagination(10, 100);

            $filters = self::getFilters(['fiscal_year_id', 'branch_id', 'status']);

            $result = Budget::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // ADD BUDGET ITEM
         $method === 'POST' && $pathParts[0] === 'budget' && ($pathParts[1] ?? '') === 'item' && ($pathParts[2] ?? '') === 'add' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('edit_budgets');

            $budgetId = self::getIdFromPath($pathParts, 3, 'Budget ID');

            $payload = self::getPayload();

            $result = Budget::addItem($budgetId, $payload);
            ResponseHelper::success($result, 'Budget item added');
         })(),

         // UPDATE BUDGET ITEM
         $method === 'PUT' && $pathParts[0] === 'budget' && ($pathParts[1] ?? '') === 'item' && ($pathParts[2] ?? '') === 'update' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('edit_budgets');

            $itemId = self::getIdFromPath($pathParts, 3, 'Item ID');

            $payload = self::getPayload();

            $result = Budget::updateItem($itemId, $payload);
            ResponseHelper::success($result, 'Budget item updated');
         })(),

         // DELETE BUDGET ITEM
         $method === 'DELETE' && $pathParts[0] === 'budget' && ($pathParts[1] ?? '') === 'item' && ($pathParts[2] ?? '') === 'delete' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('edit_budgets');

            $itemId = self::getIdFromPath($pathParts, 3, 'Item ID');

            $result = Budget::deleteItem($itemId);
            ResponseHelper::success($result, 'Budget item deleted');
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Budget endpoint not found'),
      };
   }
}

// Dispatch
BudgetRoutes::handle();
