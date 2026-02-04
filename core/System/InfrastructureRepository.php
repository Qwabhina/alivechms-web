<?php

declare(strict_types=1);

namespace AliveChMS\Core\System;

/**
 * Infrastructure Repository
 * 
 * Handles cross-cutting database concerns: Fiscal Years, Audit Logs, Branches.
 */
class InfrastructureRepository
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

    /** Fiscal Year Management */

    public function createFiscalYear(array $data): int
    {
        $result = $this->orm->insert('fiscal_year', $data);
        return (int) $result['id'];
    }

    public function updateFiscalYear(int $id, array $data): int
    {
        return $this->orm->update('fiscal_year', $data, ['FiscalYearID' => $id]);
    }

    public function deleteFiscalYear(int $id): int
    {
        return $this->orm->delete('fiscal_year', ['FiscalYearID' => $id]);
    }

    public function findFiscalYearById(int $id): ?array
    {
        $res = $this->orm->selectWithJoin(
            baseTable: 'fiscal_year fy',
            joins: [['table' => 'branch b', 'on' => 'fy.BranchID = b.BranchID', 'type' => 'LEFT']],
            fields: ['fy.*', 'b.BranchName'],
            conditions: ['fy.FiscalYearID' => ':id'],
            params: [':id' => $id]
        );
        return $res[0] ?? null;
    }

    public function isFiscalYearUsed(int $id): bool
    {
        $res = $this->orm->runQuery(
            "SELECT (SELECT COUNT(*) FROM church_budget WHERE FiscalYearID = :id) +
                    (SELECT COUNT(*) FROM contribution WHERE FiscalYearID = :id) +
                    (SELECT COUNT(*) FROM expense WHERE FiscalYearID = :id) AS total",
            [':id' => $id]
        );
        return (int) $res[0]['total'] > 0;
    }

    public function getCurrentFiscalYear(): ?int
    {
        $res = $this->orm->runQuery("SELECT FiscalYearID FROM fiscal_year WHERE Status = 'Active'");
        return $res[0]['FiscalYearID'] ?? null;
    }

    public function getAllFiscalYears(): array
    {
        $res = $this->orm->runQuery("SELECT * FROM fiscal_year");
        return $res;
    }

    /** Audit Logging */

    public function insertAuditLog(array $data): void
    {
        $this->orm->insert('audit_log', $data);
    }

    public function insertLoginLog(array $data): void
    {
        $this->orm->insert('login_log', $data);
    }

    /** Branch Management Helpers */

    public function findBranchById(int $id): ?array
    {
        $res = $this->orm->getWhere('branch', ['BranchID' => $id]);
        return $res[0] ?? null;
    }
}
