<?php

/**
 * Expense Category API Routes – v1
 *
 * Complete taxonomy management for expense classification:
 *
 * PURPOSE IN THE CHURCH
 * • Enables accurate financial reporting by ministry area
 * • Critical for budgeting, auditing, and stewardship transparency
 * • Examples: Tithes & Offerings, Missions, Building Maintenance, Staff Salaries
 *
 * BUSINESS RULES
 * • Category names must be unique system-wide
 * • Cannot delete a category currently used in any expense
 * • Simple, clean, high-performance CRUD
 *
 * "Moreover it is required of stewards that they be found faithful." — 1 Corinthians 4:2
 *
 * This is the foundation of trustworthy financial stewardship.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/ExpenseCategory.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class ExpenseCategoryRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE EXPENSE CATEGORY
         $method === 'POST' && $path === 'expensecategory/create' => (function () {
            self::authenticate();
            self::authorize('manage_expense_categories');

            $payload = self::getPayload();

            $result = ExpenseCategory::create($payload);
            ResponseHelper::created($result, 'Expense category created');
         })(),

         // UPDATE EXPENSE CATEGORY
         $method === 'PUT' && $pathParts[0] === 'expensecategory' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_expense_categories');

            $categoryId = self::getIdFromPath($pathParts, 2, 'Category ID');

            $payload = self::getPayload();

            $result = ExpenseCategory::update($categoryId, $payload);
            ResponseHelper::success($result, 'Expense category updated');
         })(),

         // DELETE EXPENSE CATEGORY
         $method === 'DELETE' && $pathParts[0] === 'expensecategory' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_expense_categories');

            $categoryId = self::getIdFromPath($pathParts, 2, 'Category ID');

            $result = ExpenseCategory::delete($categoryId);
            ResponseHelper::success($result, 'Expense category deleted');
         })(),

         // VIEW SINGLE EXPENSE CATEGORY
         $method === 'GET' && $pathParts[0] === 'expensecategory' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_expense');

            $categoryId = self::getIdFromPath($pathParts, 2, 'Category ID');

            $category = ExpenseCategory::get($categoryId);
            ResponseHelper::success($category);
         })(),

         // LIST ALL EXPENSE CATEGORIES
         $method === 'GET' && $path === 'expensecategory/all' => (function () {
            self::authenticate();
            self::authorize('view_expense');

            $result = ExpenseCategory::getAll();
            ResponseHelper::success($result);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('ExpenseCategory endpoint not found'),
      };
   }
}

// Dispatch
ExpenseCategoryRoutes::handle();
