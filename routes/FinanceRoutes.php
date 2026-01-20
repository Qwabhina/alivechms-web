<?php

/**
 * Financial Reporting API Routes – v1
 *
 * Exposes powerful, real-time financial intelligence for church leadership:
 *
 * Available Reports:
 * • Income Statement – Full breakdown of income sources vs expenses
 * • Budget vs Actual – Compare approved budgets against real expenditure
 * • Expense Summary – Top spending categories with transaction counts
 * • Contribution Summary – Giving breakdown by contribution type
 * • Balance Sheet – Simple net financial position (cash in hand)
 *
 * All reports are:
 * • Fiscal-year scoped (strict)
 * • Optionally date-range filtered
 * • Branch-aware (respects user context)
 * • Fully permission-controlled
 * • Formatted with proper currency rounding
 *
 * Ideal for treasurer reports, board meetings, and annual audits.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Finance.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class FinanceRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);
      self::authenticate();
      self::authorize('finances.view');

      // Extract common parameters
      $fiscalYearId = $pathParts[2] ?? null;
      $dateFrom     = $_GET['date_from'] ?? null;
      $dateTo       = $_GET['date_to'] ?? null;

      // Validate Fiscal Year ID presence and format
      if (!$fiscalYearId || !is_numeric($fiscalYearId)) {
         ResponseHelper::error('Fiscal Year ID is required in URL (e.g., /finance/income-statement/5)', 400);
      }

      match (true) {
         // INCOME STATEMENT
         // Example: GET /finance/income-statement/5?date_from=2025-01-01&date_to=2025-06-30
         $method === 'GET' && ($pathParts[1] ?? '') === 'income-statement' => (function () use ($fiscalYearId, $dateFrom, $dateTo) {
            $report = Finance::getIncomeStatement((int)$fiscalYearId, $dateFrom, $dateTo);
            ResponseHelper::success($report);
         })(),

         // BUDGET VS ACTUAL COMPARISON
         // Shows variance per category – critical for financial oversight
         $method === 'GET' && ($pathParts[1] ?? '') === 'budget-vs-actual' => (function () use ($fiscalYearId, $dateFrom, $dateTo) {
            $report = Finance::getBudgetVsActual((int)$fiscalYearId, $dateFrom, $dateTo);
            ResponseHelper::success($report);
         })(),

         // EXPENSE SUMMARY BY CATEGORY
         // Top-down view of where money is going – includes transaction count
         $method === 'GET' && ($pathParts[1] ?? '') === 'expense-summary' => (function () use ($fiscalYearId, $dateFrom, $dateTo) {
            $report = Finance::getExpenseSummary((int)$fiscalYearId, $dateFrom, $dateTo);
            ResponseHelper::success($report);
         })(),

         // CONTRIBUTION SUMMARY BY TYPE
         // Breaks down giving by tithe, offering, building fund, etc.
         $method === 'GET' && ($pathParts[1] ?? '') === 'contribution-summary' => (function () use ($fiscalYearId, $dateFrom, $dateTo) {
            $report = Finance::getContributionSummary((int)$fiscalYearId, $dateFrom, $dateTo);
            ResponseHelper::success($report);
         })(),

         // SIMPLE BALANCE SHEET
         // Cash-in-hand model: total income minus approved expenses
         $method === 'GET' && ($pathParts[1] ?? '') === 'balance-sheet' => (function () use ($fiscalYearId, $dateFrom, $dateTo) {
            $report = Finance::getBalanceSheet((int)$fiscalYearId, $dateFrom, $dateTo);
            ResponseHelper::success($report);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Finance report endpoint not found'),
      };
   }
}

// Dispatch
FinanceRoutes::handle();
