<?php

declare(strict_types=1);

namespace AliveChMS\Core\Stats;

use AliveChMS\Core\System\ORM;

class ExpenseStats
{
    private ORM $orm;

    public function __construct()
    {
        $this->orm = new ORM();
    }

    /**
     * Get expense statistics for a fiscal year
     */
    public function getStats(?int $fiscalYearId = null): array
    {
       // Get fiscal year
       if ($fiscalYearId) {
          $fy = $this->orm->runQuery(
             "SELECT FiscalYearID, FiscalYearName, StartDate, EndDate, Status 
              FROM fiscal_year WHERE FiscalYearID = :id",
             [':id' => $fiscalYearId]
          );
       } else {
          $fy = $this->orm->runQuery(
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
       $fyTotalResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE 1=1 $fyCondition",
          $fyParams
       )[0];

       // Approved
       $approvedResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE ApprovalStatus = 'Approved' $fyCondition",
          $fyParams
       )[0];

       // Pending
       $pendingResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE ApprovalStatus = 'Pending' $fyCondition",
          $fyParams
       )[0];

       // Declined
       $declinedResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE ApprovalStatus = 'Declined' $fyCondition",
          $fyParams
       )[0];

       // This month
       $monthStart = date('Y-m-01');
       $monthEnd = date('Y-m-t');
       $monthResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE ExpDate >= :start AND ExpDate <= :end $fyCondition",
          array_merge([':start' => $monthStart, ':end' => $monthEnd], $fyParams)
       )[0];

       // Last month
       $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
       $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
       $lastMonthResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE ExpDate >= :start AND ExpDate <= :end $fyCondition",
          array_merge([':start' => $lastMonthStart, ':end' => $lastMonthEnd], $fyParams)
       )[0];

       // This week
       $weekStart = date('Y-m-d', strtotime('monday this week'));
       $weekEnd = date('Y-m-d', strtotime('sunday this week'));
       $weekResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE ExpDate >= :start AND ExpDate <= :end $fyCondition",
          array_merge([':start' => $weekStart, ':end' => $weekEnd], $fyParams)
       )[0];

       // Today
       $today = date('Y-m-d');
       $todayResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE ExpDate = :today $fyCondition",
          array_merge([':today' => $today], $fyParams)
       )[0];

       // By category
       $byCategory = $this->orm->runQuery(
          "SELECT ec.ExpCategoryID, ec.CategoryName AS CategoryName,
                  COALESCE(SUM(e.ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e
           JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
           WHERE 1=1 $fyCondition
           GROUP BY ec.ExpCategoryID, ec.CategoryName
           ORDER BY total DESC",
          $fyParams
       );

       // By status
       $byStatus = $this->orm->runQuery(
          "SELECT ApprovalStatus, COALESCE(SUM(ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e WHERE 1=1 $fyCondition
           GROUP BY ApprovalStatus ORDER BY total DESC",
          $fyParams
       );

       // By branch
       $byBranch = $this->orm->runQuery(
          "SELECT b.BranchID, b.BranchName, COALESCE(SUM(e.ExpAmount), 0) AS total, COUNT(*) AS count
           FROM expense e
           LEFT JOIN branch b ON e.BranchID = b.BranchID
           WHERE 1=1 $fyCondition
           GROUP BY b.BranchID, b.BranchName
           ORDER BY total DESC",
          $fyParams
       );

       // Monthly trend
       $monthlyTrend = $this->orm->runQuery(
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
       $topExpenses = $this->orm->runQuery(
          "SELECT e.ExpID, e.ExpTitle, e.ExpAmount, e.ExpDate, e.ApprovalStatus,
                  ec.CategoryName AS CategoryName, b.BranchName
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
}
