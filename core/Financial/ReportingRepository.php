<?php

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\System\ORM;

/**
 * Financial Reporting Repository
 * 
 * Handles database operations for financial reports using actual schema tables.
 */
class ReportingRepository
{
   private ORM $orm;

   public function __construct()
   {
      $this->orm = new ORM();
   }

   public function getIncomeStatement(int $fiscalYearId): array
   {
      return [
         'income' => $this->getIncome($fiscalYearId),
         'expenses' => $this->getExpenses($fiscalYearId)
      ];
   }

   public function getBalanceSheet(int $fiscalYearId): array
   {
      // Balance sheet typically shows assets, liabilities, and equity
      // For a church, this maps to: income (assets), expenses (liabilities), net (equity)
      $income = $this->getIncome($fiscalYearId);
      $expenses = $this->getExpenses($fiscalYearId);

      $totalIncome = array_sum(array_column($income, 'total'));
      $totalExpense = array_sum(array_column($expenses, 'total'));

      return [
         'assets' => $income,
         'liabilities' => $expenses,
         'equity' => [['name' => 'Net Worth', 'total' => $totalIncome - $totalExpense]]
      ];
   }

   public function getBudgetVsActual(int $fiscalYearId): array
   {
      return [
         'budget' => $this->getBudget($fiscalYearId),
         'actual' => $this->getActual($fiscalYearId)
      ];
   }

   public function getContributionSummary(int $fiscalYearId): array
   {
      return [
         'contributions' => $this->getContributions($fiscalYearId),
         'pledges' => $this->getPledges($fiscalYearId)
      ];
   }

   public function getExpenseSummary(int $fiscalYearId): array
   {
      return [
         'expenses' => $this->getExpenses($fiscalYearId),
         'budget' => $this->getBudget($fiscalYearId)
      ];
   }

   private function getIncome(int $fiscalYearId): array
   {
      return $this->orm->runQuery(
         "SELECT ct.ContributionTypeName AS name, SUM(c.ContributionAmount) AS total
          FROM contribution c
          JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
          WHERE c.FiscalYearID = :fy AND c.Deleted = 0
          GROUP BY ct.ContributionTypeID
          ORDER BY total DESC",
         [':fy' => $fiscalYearId]
      );
   }

   private function getExpenses(int $fiscalYearId): array
   {
      return $this->orm->runQuery(
         "SELECT ec.CategoryName AS name, SUM(e.ExpAmount) AS total
          FROM expense e
          JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
          WHERE e.FiscalYearID = :fy AND e.ApprovalStatus = 'Approved' AND e.Deleted = 0
          GROUP BY ec.ExpCategoryID
          ORDER BY total DESC",
         [':fy' => $fiscalYearId]
      );
   }

   private function getBudget(int $fiscalYearId): array
   {
      return $this->orm->runQuery(
         "SELECT bi.ItemDescription AS name, bi.Amount AS total
          FROM budget_item bi
          JOIN church_budget cb ON bi.BudgetID = cb.BudgetID
          WHERE cb.FiscalYearID = :fy AND cb.BudgetStatus = 'Approved'
          ORDER BY bi.Amount DESC",
         [':fy' => $fiscalYearId]
      );
   }

   private function getActual(int $fiscalYearId): array
   {
      // Actual spending grouped by expense category
      return $this->getExpenses($fiscalYearId);
   }

   private function getContributions(int $fiscalYearId): array
   {
      return $this->orm->runQuery(
         "SELECT ct.ContributionTypeName AS name, 
                 SUM(c.ContributionAmount) AS total,
                 COUNT(*) AS count
          FROM contribution c
          JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
          WHERE c.FiscalYearID = :fy AND c.Deleted = 0
          GROUP BY ct.ContributionTypeID
          ORDER BY total DESC",
         [':fy' => $fiscalYearId]
      );
   }

   private function getPledges(int $fiscalYearId): array
   {
      return $this->orm->runQuery(
         "SELECT pt.PledgeTypeName AS name,
                 SUM(p.PledgeAmount) AS total,
                 COUNT(*) AS count,
                 SUM(COALESCE((SELECT SUM(PaymentAmount) FROM pledge_payment WHERE PledgeID = p.PledgeID), 0)) AS paid
          FROM pledge p
          JOIN pledge_type pt ON p.PledgeTypeID = pt.PledgeTypeID
          WHERE p.FiscalYearID = :fy
          GROUP BY pt.PledgeTypeID
          ORDER BY total DESC",
         [':fy' => $fiscalYearId]
      );
   }
}