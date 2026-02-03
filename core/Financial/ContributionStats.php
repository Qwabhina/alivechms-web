<?php

declare(strict_types=1);

namespace AliveChMS\Core\Stats;

use AliveChMS\Core\System\ORM;

class ContributionStats
{
    private ORM $orm;

    public function __construct()
    {
        $this->orm = new ORM();
    }

    /**
     * Get total contributions with filters
     *
     * @param array $filters Filters
     * @return array ['total_contribution' => string]
     */
    public function getTotal(array $filters = []): array
    {
       $conditions = ['c.Deleted = 0'];
       $params     = [];

       if (!empty($filters['contribution_type_id'])) {
          $conditions[] = 'c.ContributionTypeID = :type_id';
          $params[':type_id'] = (int)$filters['contribution_type_id'];
       }
       if (!empty($filters['fiscal_year_id'])) {
          $conditions[] = 'c.FiscalYearID = :fy_id';
          $params[':fy_id'] = (int)$filters['fiscal_year_id'];
       }
       if (!empty($filters['start_date'])) {
          $conditions[] = 'c.ContributionDate >= :start';
          $params[':start'] = $filters['start_date'];
       }
       if (!empty($filters['end_date'])) {
          $conditions[] = 'c.ContributionDate <= :end';
          $params[':end'] = $filters['end_date'];
       }

       $whereClause = implode(' AND ', $conditions);

       $result = $this->orm->runQuery(
          "SELECT COALESCE(SUM(c.ContributionAmount), 0) AS total 
           FROM contribution c 
           WHERE $whereClause",
          $params
       )[0];

       return ['total_contribution' => number_format((float)$result['total'], 2)];
    }

    /**
     * Get contribution statistics for a fiscal year
     *
     * @param int|null $fiscalYearId Optional fiscal year ID (null = active fiscal year)
     * @return array Comprehensive statistics data
     */
    public function getStats(?int $fiscalYearId = null): array
    {
       // Get fiscal year - either specified or active
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
       $fyStartDate = $fiscalYear['StartDate'] ?? date('Y-01-01');
       $fyEndDate = $fiscalYear['EndDate'] ?? date('Y-12-31');
       $fyStatus = $fiscalYear['Status'] ?? 'Unknown';

       // Fiscal year filter condition
       $fyCondition = $fyId ? "AND c.FiscalYearID = :fy_id" : "";
       $fyParams = $fyId ? [':fy_id' => $fyId] : [];

       // Total contributions for fiscal year
       $fyTotalResult = $this->orm->runQuery(
          "SELECT 
             COALESCE(SUM(ContributionAmount), 0) AS total,
             COUNT(*) AS count
          FROM contribution c
          WHERE Deleted = 0 $fyCondition",
          $fyParams
       )[0];

       // This month (within fiscal year)
       $monthStart = date('Y-m-01');
       $monthEnd = date('Y-m-t');
       $monthResult = $this->orm->runQuery(
          "SELECT 
             COALESCE(SUM(ContributionAmount), 0) AS total,
             COUNT(*) AS count
          FROM contribution c
          WHERE Deleted = 0 
          AND ContributionDate >= :start 
          AND ContributionDate <= :end
          $fyCondition",
          array_merge([':start' => $monthStart, ':end' => $monthEnd], $fyParams)
       )[0];

       // Last month (within fiscal year)
       $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
       $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
       $lastMonthResult = $this->orm->runQuery(
          "SELECT COALESCE(SUM(ContributionAmount), 0) AS total, COUNT(*) AS count
          FROM contribution c
          WHERE Deleted = 0 
          AND ContributionDate >= :start 
          AND ContributionDate <= :end
          $fyCondition",
          array_merge([':start' => $lastMonthStart, ':end' => $lastMonthEnd], $fyParams)
       )[0];

       // This week
       $weekStart = date('Y-m-d', strtotime('monday this week'));
       $weekEnd = date('Y-m-d', strtotime('sunday this week'));
       $weekResult = $this->orm->runQuery(
          "SELECT 
             COALESCE(SUM(ContributionAmount), 0) AS total,
             COUNT(*) AS count
          FROM contribution c
          WHERE Deleted = 0 
          AND ContributionDate >= :start 
          AND ContributionDate <= :end
          $fyCondition",
          array_merge([':start' => $weekStart, ':end' => $weekEnd], $fyParams)
       )[0];

       // Today
       $today = date('Y-m-d');
       $todayResult = $this->orm->runQuery(
          "SELECT 
             COALESCE(SUM(ContributionAmount), 0) AS total,
             COUNT(*) AS count
          FROM contribution c
          WHERE Deleted = 0 
          AND ContributionDate = :today
          $fyCondition",
          array_merge([':today' => $today], $fyParams)
       )[0];

       // Top contributors (fiscal year)
       $topContributors = $this->orm->runQuery(
          "SELECT 
             m.MbrID,
             m.MbrFirstName,
             m.MbrFamilyName,
             COALESCE(SUM(c.ContributionAmount), 0) AS total,
             COUNT(*) AS contribution_count
          FROM contribution c
          JOIN churchmember m ON c.MbrID = m.MbrID
          WHERE c.Deleted = 0 $fyCondition
          GROUP BY c.MbrID, m.MbrFirstName, m.MbrFamilyName
          ORDER BY total DESC
          LIMIT 10",
          $fyParams
       );

       // Contributions by type (fiscal year)
       $byType = $this->orm->runQuery(
          "SELECT 
             ct.ContributionTypeID,
             ct.ContributionTypeName,
             COALESCE(SUM(c.ContributionAmount), 0) AS total,
             COUNT(*) AS count
          FROM contribution c
          JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
          WHERE c.Deleted = 0 $fyCondition
          GROUP BY ct.ContributionTypeID, ct.ContributionTypeName
          ORDER BY total DESC",
          $fyParams
       );

       // Contributions by payment method (fiscal year)
       $byPaymentMethod = $this->orm->runQuery(
          "SELECT 
             pm.PaymentMethodID,
             pm.PaymentMethodName,
             COALESCE(SUM(c.ContributionAmount), 0) AS total,
             COUNT(*) AS count
          FROM contribution c
          JOIN payment_method pm ON c.PaymentMethodID = pm.PaymentMethodID
          WHERE c.Deleted = 0 $fyCondition
          GROUP BY pm.PaymentMethodID, pm.PaymentMethodName
          ORDER BY total DESC",
          $fyParams
       );

       // Monthly trend (last 12 months or fiscal year months)
       $monthlyTrend = $this->orm->runQuery(
          "SELECT 
             DATE_FORMAT(ContributionDate, '%Y-%m') AS month,
             DATE_FORMAT(ContributionDate, '%b %Y') AS month_label,
             COALESCE(SUM(ContributionAmount), 0) AS total,
             COUNT(*) AS count
          FROM contribution c
          WHERE Deleted = 0 
          AND ContributionDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
          $fyCondition
          GROUP BY DATE_FORMAT(ContributionDate, '%Y-%m')
          ORDER BY month ASC",
          $fyParams
       );

       // Unique contributors count (fiscal year)
       $uniqueContributors = $this->orm->runQuery(
          "SELECT COUNT(DISTINCT MbrID) AS count
          FROM contribution c
          WHERE Deleted = 0 $fyCondition",
          $fyParams
       )[0]['count'];

       // Calculate statistics
       $fyTotal = (float)$fyTotalResult['total'];
       $fyCount = (int)$fyTotalResult['count'];
       $avgAmount = $fyCount > 0 ? $fyTotal / $fyCount : 0;

       $monthTotal = (float)$monthResult['total'];
       $lastMonthTotal = (float)$lastMonthResult['total'];
       $monthGrowth = $lastMonthTotal > 0 ? (($monthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;

       // Average per contributor
       $avgPerContributor = $uniqueContributors > 0 ? $fyTotal / $uniqueContributors : 0;

       return [
          'fiscal_year' => [
             'id' => $fyId,
             'name' => $fiscalYearName,
             'start_date' => $fyStartDate,
             'end_date' => $fyEndDate,
             'status' => $fyStatus
          ],
          'total_amount' => $fyTotal,
          'total_count' => $fyCount,
          'average_amount' => round($avgAmount, 2),
          'average_per_contributor' => round($avgPerContributor, 2),
          'unique_contributors' => (int)$uniqueContributors,
          'month_total' => $monthTotal,
          'month_count' => (int)$monthResult['count'],
          'month_growth' => round($monthGrowth, 1),
          'last_month_total' => $lastMonthTotal,
          'week_total' => (float)$weekResult['total'],
          'week_count' => (int)$weekResult['count'],
          'today_total' => (float)$todayResult['total'],
          'today_count' => (int)$todayResult['count'],
          'top_contributors' => $topContributors,
          'by_type' => $byType,
          'by_payment_method' => $byPaymentMethod,
          'monthly_trend' => $monthlyTrend
       ];
    }
}
