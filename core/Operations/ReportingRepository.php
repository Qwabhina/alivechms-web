<?php

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\System\ORM;

/**
 * Reporting Repository
 * 
 * Centralizes complex analytical queries, aggregations, and trend lookups.
 */
class ReportingRepository
{
    private ORM $orm;

    public function __construct()
    {
        $this->orm = new ORM();
    }

    public function getDashboardStats(int $branchId): array
    {
        $today = date('Y-m-d');
        $month = date('Y-m-01');
        $year  = date('Y-01-01');

        return $this->orm->runQuery("
            SELECT
                (SELECT COUNT(*) FROM churchmember WHERE BranchID = :b1 AND Deleted = 0) AS total,
                (SELECT COUNT(*) FROM churchmember WHERE BranchID = :b2 AND MbrRegistrationDate >= :today AND Deleted = 0) AS new_today,
                (SELECT COUNT(*) FROM churchmember WHERE BranchID = :b3 AND MbrRegistrationDate >= :month AND Deleted = 0) AS new_this_month
            ", [':b1' => $branchId, ':b2' => $branchId, ':b3' => $branchId, ':today' => $today, ':month' => $month]
        )[0];
    }

    public function getFinanceOverview(int $branchId, int $fiscalYearId): array
    {
        return $this->orm->runQuery("
            SELECT 
                (SELECT COALESCE(SUM(ContributionAmount), 0) FROM contribution WHERE BranchID = :b1 AND FiscalYearID = :f1 AND Deleted = 0) AS income,
                (SELECT COALESCE(SUM(ExpAmount), 0) FROM expense WHERE BranchID = :b2 AND FiscalYearID = :f2 AND ApprovalStatus = 'Approved' AND Deleted = 0) AS expenses
            ", [':b1' => $branchId, ':f1' => $fiscalYearId, ':b2' => $branchId, ':f2' => $fiscalYearId]
        )[0];
    }

    public function getActiveFiscalYearId(int $branchId): ?int
    {
        $res = $this->orm->runQuery("SELECT FiscalYearID FROM fiscal_year WHERE BranchID = :b1 AND Status = 'Active' LIMIT 1", [':b1' => $branchId]);
        return isset($res[0]) ? (int)$res[0]['FiscalYearID'] : null;
    }

    public function getUpcomingEventsCount(int $branchId): int
    {
        $res = $this->orm->runQuery("SELECT COUNT(*) AS total FROM church_event WHERE BranchID = :b1 AND EventDateTime >= NOW()", [':b1' => $branchId]);
        return isset($res[0]) ? (int)$res[0]['total'] : 0;
    }

    public function getRecentMembers(int $branchId, int $limit = 5): array
    {
        return $this->orm->runQuery("
            SELECT MbrID, MbrFirstName, MbrFamilyName, MbrProfilePicture, MbrRegistrationDate
            FROM churchmember WHERE BranchID = :b1 AND Deleted = 0
            ORDER BY MbrRegistrationDate DESC LIMIT :limit",
            [':b1' => $branchId, ':limit' => $limit]
        );
    }

    public function getIncomeStatement(int $fiscalYearId): array
    {
        return [
            'income' => $this->orm->runQuery("
                SELECT ct.ContributionTypeName as name, SUM(c.ContributionAmount) as total
                FROM contribution c JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
                WHERE c.FiscalYearID = :fy AND c.Deleted = 0 GROUP BY ct.ContributionTypeID", [':fy' => $fiscalYearId]),
            'expenses' => $this->orm->runQuery("
                SELECT ec.CategoryName as name, SUM(e.ExpAmount) as total
                FROM expense e JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
                WHERE e.FiscalYearID = :fy AND e.ApprovalStatus = 'Approved' AND e.Deleted = 0 GROUP BY ec.ExpCategoryID", [':fy' => $fiscalYearId])
        ];
    }
}
