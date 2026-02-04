<?php

/**
 * Financial Reporting Service
 *
 * Orchestrates financial reports and delegates data fetching to ReportingRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\Financial\ReportingRepository;

class Finance
{
   public static function getIncomeStatement(int $fiscalYearId): array
   {
      $repo = new ReportingRepository();
      $data = $repo->getIncomeStatement($fiscalYearId);

      $totalIncome = array_sum(array_column($data['income'], 'total'));
      $totalExpense = array_sum(array_column($data['expenses'], 'total'));

      return [
         'income' => $data['income'],
         'expenses' => $data['expenses'],
         'totals' => [
            'income' => $totalIncome,
            'expense' => $totalExpense,
            'net' => $totalIncome - $totalExpense
         ]
      ];
   }

   public static function getBalanceSheet(int $fiscalYearId): array
   {
      $repo = new ReportingRepository();
      $data = $repo->getBalanceSheet($fiscalYearId);

      $totalAssets = array_sum(array_column($data['assets'], 'total'));
      $totalLiabilities = array_sum(array_column($data['liabilities'], 'total'));
      $totalEquity = array_sum(array_column($data['equity'], 'total'));

      return [
         'assets' => $data['assets'],
         'liabilities' => $data['liabilities'],
         'equity' => $data['equity'],
         'totals' => [
            'assets' => $totalAssets,
            'liabilities' => $totalLiabilities,
            'equity' => $totalEquity,
            'net' => $totalAssets - $totalLiabilities - $totalEquity
         ]
      ];
   }

   public static function getBudgetVsActual(int $fiscalYearId): array
   {
      $repo = new ReportingRepository();
      $data = $repo->getBudgetVsActual($fiscalYearId);

      $totalBudget = array_sum(array_column($data['budget'], 'total'));
      $totalActual = array_sum(array_column($data['actual'], 'total'));

      return [
         'budget' => $data['budget'],
         'actual' => $data['actual'],
         'totals' => [
            'budget' => $totalBudget,
            'actual' => $totalActual,
            'net' => $totalBudget - $totalActual
         ]
      ];
   }

   public static function getContributionSummary(int $fiscalYearId): array
   {
      $repo = new ReportingRepository();
      $data = $repo->getContributionSummary($fiscalYearId);

      $totalContributions = array_sum(array_column($data['contributions'], 'total'));
      $totalPledges = array_sum(array_column($data['pledges'], 'total'));

      return [
         'contributions' => $data['contributions'],
         'pledges' => $data['pledges'],
         'totals' => [
            'contributions' => $totalContributions,
            'pledges' => $totalPledges,
            'net' => $totalContributions - $totalPledges
         ]
      ];
   }

   public static function getExpenseSummary(int $fiscalYearId): array
   {
      $repo = new ReportingRepository();
      $data = $repo->getExpenseSummary($fiscalYearId);

      $totalExpenses = array_sum(array_column($data['expenses'], 'total'));
      $totalBudget = array_sum(array_column($data['budget'], 'total'));

      return [
         'expenses' => $data['expenses'],
         'budget' => $data['budget'],
         'totals' => [
            'expenses' => $totalExpenses,
            'budget' => $totalBudget,
            'net' => $totalExpenses - $totalBudget
         ]
      ];
   }
}