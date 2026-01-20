<?php

/**
 * Fiscal Year API Routes – v1
 *
 * Complete fiscal year lifecycle management with strict financial integrity:
 *
 * Key Capabilities:
 * • Create new fiscal years with automatic overlap protection
 * • Update fiscal year boundaries (only while open)
 * • Safely delete fiscal years with no associated transactions
 * • Close fiscal year (irreversible — locks all financial data)
 * • View single fiscal year with branch context
 * • Powerful paginated listing with multi-filter support
 *
 * Business Rules Enforced:
 * • Only one active fiscal year per branch at a time
 * • No overlapping date ranges allowed
 * • Cannot modify dates of a closed fiscal year
 * • Cannot delete fiscal year with budgets, contributions, or expenses
 *
 * Critical for audit compliance, financial reporting, and year-end processes.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/FiscalYear.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class FiscalYearRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE NEW FISCAL YEAR
         $method === 'POST' && $path === 'fiscalyear/create' => (function () {
            self::authenticate();
            self::authorize('settings.edit');

            $payload = self::getPayload();

            $result = FiscalYear::create($payload);
            ResponseHelper::created($result, 'Fiscal year created');
         })(),

         // UPDATE FISCAL YEAR
         $method === 'POST' && $pathParts[0] === 'fiscalyear' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('settings.edit');

            $fiscalYearId = self::getIdFromPath($pathParts, 2, 'Fiscal Year ID');

            $payload = self::getPayload();

            $result = FiscalYear::update($fiscalYearId, $payload);
            ResponseHelper::success($result, 'Fiscal year updated');
         })(),

         // DELETE FISCAL YEAR (Only if no financial records)
         $method === 'POST' && $pathParts[0] === 'fiscalyear' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('settings.edit');

            $fiscalYearId = self::getIdFromPath($pathParts, 2, 'Fiscal Year ID');

            $result = FiscalYear::delete($fiscalYearId);
            ResponseHelper::success($result, 'Fiscal year deleted');
         })(),

         // VIEW SINGLE FISCAL YEAR
         $method === 'GET' && $pathParts[0] === 'fiscalyear' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('finances.view');

            $fiscalYearId = self::getIdFromPath($pathParts, 2, 'Fiscal Year ID');

            $fiscalYear = FiscalYear::get($fiscalYearId);
            ResponseHelper::success($fiscalYear);
         })(),

         // LIST ALL FISCAL YEARS (Paginated + Multi-Filter)
         $method === 'GET' && $path === 'fiscalyear/all' => (function () {
            self::authenticate();
            self::authorize('finances.view');

            [$page, $limit] = self::getPagination(10, 100);

            $filters = [];
            if (isset($_GET['branch_id']) && is_numeric($_GET['branch_id'])) {
               $filters['branch_id'] = (int)$_GET['branch_id'];
            }
            if (isset($_GET['status']) && in_array($_GET['status'], ['Active', 'Closed'])) {
               $filters['status'] = $_GET['status'];
            }
            if (!empty($_GET['date_from'])) {
               $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
               $filters['date_to'] = $_GET['date_to'];
            }

            $result = FiscalYear::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // CLOSE FISCAL YEAR (Irreversible)
         $method === 'POST' && $pathParts[0] === 'fiscalyear' && ($pathParts[1] ?? '') === 'close' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('settings.edit');

            $fiscalYearId = self::getIdFromPath($pathParts, 2, 'Fiscal Year ID');

            $result = FiscalYear::close($fiscalYearId);
            ResponseHelper::success($result, 'Fiscal year closed');
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Fiscal year endpoint not found'),
      };
   }
}

// Dispatch
FiscalYearRoutes::handle();
