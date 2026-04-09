<?php

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\System\ORM;
use Exception;

/**
 * Budget Repository
 * 
 * Handles database operations for budgets and budget line items.
 * Note: Table names normalized to match schema (budget_item instead of budget_items).
 */
class BudgetRepository
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

    /**
     * Create a new budget header
     */
    public function create(array $data): int
    {
        $result = $this->orm->insert('church_budget', $data);
        return (int)$result['id'];
    }

    /**
     * Update budget header
     */
    public function update(int $id, array $data): int
    {
        return $this->orm->update('church_budget', $data, ['BudgetID' => $id]);
    }

    /**
     * Get budget by ID with metadata
     */
    public function findById(int $id): ?array
    {
        $result = $this->orm->selectWithJoin(
            baseTable: 'church_budget b',
            joins: [
                ['table' => 'fiscal_year f',  'on' => 'b.FiscalYearID = f.FiscalYearID'],
                ['table' => 'branch br',      'on' => 'b.BranchID = br.BranchID'],
                ['table' => 'churchmember c', 'on' => 'b.CreatedBy = c.MbrID', 'type' => 'LEFT'],
                ['table' => 'churchmember a', 'on' => 'b.ApprovedBy = a.MbrID', 'type' => 'LEFT']
            ],
            fields: [
                'b.*',
                'f.FiscalYearName AS FiscalYear',
                'br.BranchName',
                'c.MbrFirstName AS CreatorFirstName',
                'c.MbrFamilyName AS CreatorFamilyName',
                'a.MbrFirstName AS ApproverFirstName',
                'a.MbrFamilyName AS ApproverFamilyName'
            ],
            conditions: ['b.BudgetID' => ':id'],
            params: [':id' => $id]
        );

        return $result[0] ?? null;
    }

    /**
     * Find all budgets with filters
     */
    public function findAll(int $limit, int $offset, array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['fiscal_year_id'])) {
            $conditions['b.FiscalYearID'] = ':fy';
            $params[':fy'] = (int)$filters['fiscal_year_id'];
        }
        if (!empty($filters['branch_id'])) {
            $conditions['b.BranchID'] = ':br';
            $params[':br'] = (int)$filters['branch_id'];
        }
        if (!empty($filters['status'])) {
            $conditions['b.BudgetStatus'] = ':st';
            $params[':st'] = $filters['status'];
        }

        $budgets = $this->orm->selectWithJoin(
            baseTable: 'church_budget b',
            joins: [
                ['table' => 'fiscal_year f', 'on' => 'b.FiscalYearID = f.FiscalYearID'],
                ['table' => 'branch br',     'on' => 'b.BranchID = br.BranchID']
            ],
            fields: [
                'b.BudgetID',
                'b.BudgetTitle',
                'b.TotalAmount',
                'b.BudgetStatus',
                'b.CreatedAt',
                'f.FiscalYearName',
                'br.BranchName'
            ],
            conditions: $conditions,
            params: $params,
            orderBy: ['b.CreatedAt' => 'DESC'],
            limit: $limit,
            offset: $offset
        );

        // Calculate total count
        // $whereSql = ["Deleted = 0"];
        // foreach ($conditions as $k => $v) {
        // if ($k === 'b.Deleted') continue;
        //     $col = str_replace('b.', '', $k);
        //     $whereSql[] = "$col = $v";
        // }
        // $whereClause = "WHERE " . implode(' AND ', $whereSql);

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM church_budget",
            $params
        )[0]['total'];

        return [
            'data' => $budgets,
            'total' => (int)$total
        ];
    }

    /**
     * Item Management (Table: budget_item)
     */

    public function insertItem(array $data): void
    {
        $this->orm->insert('budget_item', $data);
    }

    public function updateItem(int $itemId, array $data): void
    {
        $this->orm->update('budget_item', $data, ['ItemID' => $itemId]);
    }

    public function deleteItem(int $itemId): void
    {
        $this->orm->delete('budget_item', ['ItemID' => $itemId]);
    }

    public function getItems(int $budgetId): array
    {
        return $this->orm->getWhere('budget_item', ['BudgetID' => $budgetId]);
    }

    public function getItem(int $itemId): ?array
    {
        $res = $this->orm->getWhere('budget_item', ['ItemID' => $itemId]);
        return $res[0] ?? null;
    }

    /**
     * Recalculate and update the total amount of a budget
     */
    public function recalculateTotal(int $budgetId): float
    {
        $items = $this->getItems($budgetId);
        $total = array_sum(array_column($items, 'Amount'));
        $this->update($budgetId, ['TotalAmount' => (float)$total]);
        return (float)$total;
    }

    /**
     * Validation Helpers
     */
    public function isValidFiscalYear(int $id): bool
    {
        return !empty($this->orm->getWhere('fiscal_year', ['FiscalYearID' => $id, 'Status' => 'Active']));
    }

    public function isValidBranch(int $id): bool
    {
        return !empty($this->orm->getWhere('branch', ['BranchID' => $id, 'IsActive' => 1]));
    }
}
