<?php

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\System\ORM;

/**
 * Expense Repository
 * 
 * Handles database operations for expenses and approvals.
 */
class ExpenseRepository
{
    private ORM $orm;

    public function __construct()
    {
        $this->orm = new ORM();
    }

    public function beginTransaction(): void 
    {
        $this->orm->beginTransaction();
    }

    public function commit(): void 
    {
        $this->orm->commit();
    }

    public function rollBack(): void 
    {
        $this->orm->rollBack();
    }

    public function create(array $data): int
    {
        $result = $this->orm->insert('expense', $data);
        return (int)$result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update('expense', $data, ['ExpID' => $id]);
    }

    public function findById(int $id): ?array
    {
        $result = $this->orm->runQuery(
            "SELECT e.*, 
                    ec.CategoryName AS CategoryName,
                    fy.FiscalYearName,
                    b.BranchName,
                    r.MbrFirstName AS RequesterFirstName,
                    r.MbrFamilyName AS RequesterFamilyName,
                    ea.ApproverID,
                    ea.ApprovalStatus,
                    ea.ApprovalComments AS ApprovalRemarks,
                    ea.ApprovedAt AS ApprovedAt,
                    a.MbrFirstName AS ApproverFirstName,
                    a.MbrFamilyName AS ApproverFamilyName
             FROM expense e
             JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
             LEFT JOIN fiscal_year fy ON e.FiscalYearID = fy.FiscalYearID
             LEFT JOIN branch b ON e.BranchID = b.BranchID
             LEFT JOIN churchmember r ON e.RequestedBy = r.MbrID
             LEFT JOIN expense_approval ea ON e.ExpID = ea.ExpID
             LEFT JOIN churchmember a ON ea.ApproverID = a.MbrID
             WHERE e.ExpID = :id",
            [':id' => $id]
        );

        return $result[0] ?? null;
    }

    public function findAll(int $limit, int $offset, array $filters = [], string $orderBy = 'e.ExpDate DESC'): array
    {
        $where = ['e.Deleted = 0'];
        $params = [];

        if (!empty($filters['fiscal_year_id'])) {
            $where[] = 'e.FiscalYearID = :fy';
            $params[':fy'] = (int)$filters['fiscal_year_id'];
        }
        if (!empty($filters['branch_id'])) {
            $where[] = 'e.BranchID = :br';
            $params[':br'] = (int)$filters['branch_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'e.ExpCategoryID = :cat';
            $params[':cat'] = (int)$filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'e.ApprovalStatus = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $where[] = 'e.ExpDate >= :start';
            $params[':start'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = 'e.ExpDate <= :end';
            $params[':end'] = $filters['end_date'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT e.ExpID, e.ExpTitle, e.ExpDescription, e.ExpAmount, e.ExpDate, e.ApprovalStatus,
                       e.ExpCategoryID, e.FiscalYearID, e.BranchID, e.ReceiptImageURL,
                       ec.CategoryName, fy.FiscalYearName, b.BranchName
                FROM expense e
                JOIN expense_category ec ON e.ExpCategoryID = ec.ExpCategoryID
                LEFT JOIN fiscal_year fy ON e.FiscalYearID = fy.FiscalYearID
                LEFT JOIN branch b ON e.BranchID = b.BranchID
                $whereClause
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

        $data = $this->orm->runQuery($sql, array_merge($params, [':limit' => $limit, ':offset' => $offset]));

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM expense e $whereClause",
            $params
        )[0]['total'];

        return [
            'data' => $data,
            'total' => (int)$total
        ];
    }

    public function logApproval(array $data): void
    {
        $this->orm->insert('expense_approval', $data);
    }

    public function validateCategory(int $id): bool
    {
        $res = $this->orm->runQuery(
            "SELECT COUNT(*) AS cnt FROM expense_category WHERE ExpCategoryID = :id AND IsActive = 1",
            [':id' => $id]
        );
        return $res[0]['cnt'] > 0;
    }

    public function validateFiscalYear(int $id): bool
    {
        $res = $this->orm->runQuery(
            "SELECT COUNT(*) AS cnt FROM fiscal_year WHERE FiscalYearID = :id AND Status = 'Active'",
            [':id' => $id]
        );
        return $res[0]['cnt'] > 0;
    }

    public function validateBranch(int $id): bool
    {
        $res = $this->orm->runQuery(
            "SELECT COUNT(*) AS cnt FROM branch WHERE BranchID = :id AND IsActive = 1",
            [':id' => $id]
        );
        return $res[0]['cnt'] > 0;
    }
}
