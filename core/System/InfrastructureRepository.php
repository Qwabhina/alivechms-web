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
        // Map login event to unified audit_log structure
        $auditData = [
            'user_id' => $data['user_id'] ?? null,
            'action' => ($data['success'] ?? false) ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED',
            'entity_type' => 'Authentication',
            'entity_id' => $data['user_id'] ?? 0,
            'changes' => null,
            'metadata' => json_encode([
                'username' => $data['username'] ?? 'unknown',
                'success' => $data['success'] ?? false
            ]),
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s')
        ];

        $this->orm->insert('audit_log', $auditData);
    }

    /** Branch Management Helpers */

    public function findBranchById(int $id): ?array
    {
        $res = $this->orm->getWhere('branch', ['BranchID' => $id]);
        return $res[0] ?? null;
    }

    /** Audit Log Retrieval */

    public function searchAuditLogs(array $filters, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $conditions = [];
        $params = [];

        $where = "WHERE 1=1";

        if (!empty($filters['user_id'])) {
            $where .= " AND a.user_id = :user_id";
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['entity_type'])) {
            $where .= " AND a.entity_type = :entity_type";
            $params[':entity_type'] = $filters['entity_type'];
        }
        if (!empty($filters['entity_id'])) {
            $where .= " AND a.entity_id = :entity_id";
            $params[':entity_id'] = (int) $filters['entity_id'];
        }
        if (!empty($filters['action'])) {
            $where .= " AND a.action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND a.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND a.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        // Note: Joining with user_authentication as defined in AuthRepository, though table missing in provided SQL dump
        $logs = $this->orm->runQuery(
            "SELECT a.*, m.MbrFirstName, m.MbrFamilyName
             FROM audit_log a
             LEFT JOIN user_authentication u ON a.user_id = u.UserID
             LEFT JOIN churchmember m ON u.MbrID = m.MbrID
             $where
             ORDER BY a.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset])
        );

        $total = $this->orm->runQuery("SELECT COUNT(*) AS total FROM audit_log a $where", $params)[0]['total'];

        return [
            'data' => $logs,
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function getEntityLogs(string $entityType, int $entityId, int $limit): array
    {
        return $this->orm->runQuery(
            "SELECT a.*, m.MbrFirstName, m.MbrFamilyName
             FROM audit_log a
             LEFT JOIN user_authentication u ON a.user_id = u.UserID
             LEFT JOIN churchmember m ON u.MbrID = m.MbrID
             WHERE a.entity_type = :type AND a.entity_id = :id
             ORDER BY a.created_at DESC
             LIMIT :limit",
            [':type' => $entityType, ':id' => $entityId, ':limit' => $limit]
        );
    }

    public function getUserActivity(int $userId, int $limit): array
    {
        return $this->orm->runQuery(
            "SELECT * FROM audit_log 
             WHERE user_id = :uid 
             ORDER BY created_at DESC 
             LIMIT :limit",
            [':uid' => $userId, ':limit' => $limit]
        );
    }
}
