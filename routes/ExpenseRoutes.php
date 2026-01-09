<?php

/**
 * Expense API Routes – v1
 *
 * Full expense lifecycle with approval workflow:
 * - Create expense request
 * - View single expense
 * - Paginated listing with powerful filtering
 * - Review (approve/reject)
 * - Cancel pending expense
 *
 * All operations strictly permission-controlled.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Expense.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class ExpenseRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE EXPENSE REQUEST
         $method === 'POST' && $path === 'expense/create' => (function () {
            self::authenticate();
            self::authorize('create_expense');

            $payload = self::getPayload();

            $result = Expense::create($payload);
            ResponseHelper::created($result, 'Expense created');
         })(),

         // VIEW SINGLE EXPENSE
         $method === 'GET' && $pathParts[0] === 'expense' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_expenses');

            $expenseId = self::getIdFromPath($pathParts, 2, 'Expense ID');

            $expense = Expense::get($expenseId);
            ResponseHelper::success($expense);
         })(),

         // LIST ALL EXPENSES (Paginated + Filtered)
         $method === 'GET' && $path === 'expense/all' => (function () {
            self::authenticate();
            self::authorize('view_expenses');

            [$page, $limit] = self::getPagination(10, 100);

            $filters = self::getFilters([
               'fiscal_year_id',
               'branch_id',
               'category_id',
               'status',
               'start_date',
               'end_date',
               'search'
            ]);

            // Get sorting parameters with allowed columns
            [$sortBy, $sortDir] = self::getSorting(
               'ExpenseDate',
               'DESC',
               ['ExpenseTitle', 'ExpenseAmount', 'ExpenseDate', 'CategoryName', 'BranchName', 'ExpenseStatus']
            );
            $filters['sort_by'] = $sortBy;
            $filters['sort_dir'] = $sortDir;

            $result = Expense::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // REVIEW EXPENSE (Approve/Reject)
         $method === 'POST' && $pathParts[0] === 'expense' && ($pathParts[1] ?? '') === 'review' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('approve_expenses');

            $expenseId = self::getIdFromPath($pathParts, 2, 'Expense ID');

            $payload = self::getPayload([
               'action' => 'required|in:approve,reject',
               'remarks' => 'nullable|max:500'
            ]);

            $result = Expense::review(
               $expenseId,
               $payload['action'],
               $payload['remarks'] ?? null
            );
            ResponseHelper::success($result, 'Expense reviewed');
         })(),

         // CANCEL PENDING EXPENSE
         $method === 'POST' && $pathParts[0] === 'expense' && ($pathParts[1] ?? '') === 'cancel' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('cancel_expenses');

            $expenseId = self::getIdFromPath($pathParts, 2, 'Expense ID');

            $payload = self::getPayload([
               'reason' => 'required|max:500'
            ]);

            $result = Expense::cancel($expenseId, $payload['reason']);
            ResponseHelper::success($result, 'Expense cancelled');
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Expense endpoint not found'),
      };
   }
}

// Dispatch
ExpenseRoutes::handle();
