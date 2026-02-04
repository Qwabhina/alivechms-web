<?php

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\System\ORM;

/**
 * Contribution Repository
 * 
 * Handles all database operations for contributions.
 */
class ContributionRepository
{
    private ORM $orm;

    public function __construct()
    {
        $this->orm = new ORM();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->orm->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->orm->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollBack(): void
    {
        $this->orm->rollBack();
    }

    /**
     * Create a new contribution
     */
    public function create(array $data): int
    {
        $result = $this->orm->insert('contribution', $data);
        return (int)$result['id'];
    }

    /**
     * Update a contribution
     */
    public function update(int $id, array $data): int
    {
        return $this->orm->update('contribution', $data, ['ContributionID' => $id]);
    }

    /**
     * Find contribution by ID
     */
    public function findById(int $id): ?array
    {
        $result = $this->orm->selectWithJoin(
            baseTable: 'contribution c',
            joins: [
                ['table' => 'churchmember m',       'on' => 'c.MbrID = m.MbrID'],
                ['table' => 'contribution_type ct', 'on' => 'c.ContributionTypeID = ct.ContributionTypeID'],
                ['table' => 'payment_method pm',    'on' => 'c.PaymentMethodID = pm.PaymentMethodID'],
                ['table' => 'fiscal_year fy',       'on' => 'c.FiscalYearID = fy.FiscalYearID', 'type' => 'LEFT'],
                ['table' => 'branch b',             'on' => 'c.BranchID = b.BranchID', 'type' => 'LEFT']
            ],
            fields: [
                'c.*',
                'm.MbrFirstName',
                'm.MbrFamilyName',
                'm.MbrEmailAddress',
                'm.MbrProfilePicture',
                'ct.ContributionTypeName',
                'pm.PaymentMethodName',
                'fy.FiscalYearName',
                'b.BranchName',
                'b.BranchAddress',
                'b.BranchPhoneNumber',
                'b.BranchEmailAddress'
            ],
            conditions: ['c.ContributionID' => ':id', 'c.Deleted' => 0],
            params: [':id' => $id]
        );

        return $result[0] ?? null;
    }

    /**
     * Find all contributions with filters and pagination
     */
    public function findAll(int $limit, int $offset, array $filters = [], array $orderBy = ['c.ContributionDate' => 'DESC']): array
    {
        $includeDeleted = !empty($filters['include_deleted']);
        $conditions = $includeDeleted ? [] : ['c.Deleted' => 0];
        $params = [];

        if (!empty($filters['contribution_type_id'])) {
            $conditions['c.ContributionTypeID'] = ':type_id';
            $params[':type_id'] = (int)$filters['contribution_type_id'];
        }
        if (!empty($filters['member_id'])) {
            $conditions['c.MbrID'] = ':member_id';
            $params[':member_id'] = (int)$filters['member_id'];
        }
        if (!empty($filters['fiscal_year_id'])) {
            $conditions['c.FiscalYearID'] = ':fy_id';
            $params[':fy_id'] = (int)$filters['fiscal_year_id'];
        }
        if (!empty($filters['branch_id'])) {
            $conditions['c.BranchID'] = ':branch_id';
            $params[':branch_id'] = (int)$filters['branch_id'];
        }
        if (!empty($filters['start_date'])) {
            $conditions['c.ContributionDate >='] = ':start';
            $params[':start'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions['c.ContributionDate <='] = ':end';
            $params[':end'] = $filters['end_date'];
        }

        $result = $this->orm->selectWithJoin(
            baseTable: 'contribution c',
            joins: [
                ['table' => 'churchmember m',       'on' => 'c.MbrID = m.MbrID'],
                ['table' => 'contribution_type ct', 'on' => 'c.ContributionTypeID = ct.ContributionTypeID'],
                ['table' => 'payment_method pm',    'on' => 'c.PaymentMethodID = pm.PaymentMethodID'],
                ['table' => 'fiscal_year fy',       'on' => 'c.FiscalYearID = fy.FiscalYearID', 'type' => 'LEFT']
            ],
            fields: [
                'c.ContributionID',
                'c.ContributionAmount',
                'c.ContributionDate',
                'c.Notes',
                'c.MbrID',
                'c.ContributionTypeID',
                'c.PaymentMethodID',
                'c.FiscalYearID',
                'c.Deleted',
                'm.MbrFirstName',
                'm.MbrFamilyName',
                'ct.ContributionTypeName',
                'pm.PaymentMethodName',
                'fy.FiscalYearName'
            ],
            conditions: $conditions,
            params: $params,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset
        );

        // Calculate total count (simplified for performance)
        $whereSql = [];
        foreach ($conditions as $key => $val) {
            // Handle operator syntax in keys like "Date >="
            $col = explode(' ', $key)[0];
            $op = strpos($key, ' ') !== false ? substr($key, strpos($key, ' ') + 1) : '=';
            $whereSql[] = "$col $op $val";
        }
        $whereClause = !empty($whereSql) ? 'WHERE ' . implode(' AND ', $whereSql) : '';

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM contribution c $whereClause",
            $params
        )[0]['total'];

        return [
            'data' => $result,
            'total' => (int)$total
        ];
    }

    /**
     * Get contributions by member for a specific fiscal year
     */
    public function findByMember(int $memberId, ?int $fiscalYearId = null): array
    {
        $sql = "SELECT 
                    c.ContributionID,
                    c.ContributionAmount,
                    c.ContributionDate,
                    c.Notes,
                    ct.ContributionTypeName,
                    pm.PaymentMethodName
                FROM contribution c
                JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
                JOIN payment_method pm ON c.PaymentMethodID = pm.PaymentMethodID
                WHERE c.MbrID = :member_id AND c.Deleted = 0";

        $params = [':member_id' => $memberId];

        if ($fiscalYearId) {
            $sql .= " AND c.FiscalYearID = :fy_id";
            $params[':fy_id'] = $fiscalYearId;
        }

        $sql .= " ORDER BY c.ContributionDate DESC";

        return $this->orm->runQuery($sql, $params);
    }

    /**
     * Get aggregated totals by type for a member
     */
    public function getTotalsByType(int $memberId, ?int $fiscalYearId = null): array
    {
        $sql = "SELECT 
                    ct.ContributionTypeName,
                    COALESCE(SUM(c.ContributionAmount), 0) AS total,
                    COUNT(*) AS count
                FROM contribution c
                JOIN contribution_type ct ON c.ContributionTypeID = ct.ContributionTypeID
                WHERE c.MbrID = :member_id AND c.Deleted = 0";
        
        $params = [':member_id' => $memberId];

        if ($fiscalYearId) {
            $sql .= " AND c.FiscalYearID = :fy_id";
            $params[':fy_id'] = $fiscalYearId;
        }

        $sql .= " GROUP BY ct.ContributionTypeID, ct.ContributionTypeName
                  ORDER BY total DESC";

        return $this->orm->runQuery($sql, $params);
    }

    /**
     * Get all active contribution types
     */
    public function getTypes(): array
    {
         return $this->orm->runQuery(
            "SELECT ContributionTypeID, ContributionTypeName, ContributionTypeDescription, IsActive, IsTaxDeductible 
             FROM contribution_type 
             WHERE IsActive = 1 
             ORDER BY ContributionTypeName"
        );
    }

    /**
     * Get all active payment methods
     */
    public function getPaymentMethods(): array
    {
        return $this->orm->runQuery(
            "SELECT PaymentMethodID, PaymentMethodName, DisplayOrder, IsActive 
             FROM payment_method 
             WHERE IsActive = 1 
             ORDER BY DisplayOrder"
        );
    }

    /**
     * Validate foreign keys for creation
     */
    public function validateForeignKeys(int $memberId, int $typeId, int $paymentMethodId, ?int $fiscalYearId = null): array
    {
        $sql = "SELECT
            (SELECT COUNT(*) FROM churchmember c 
             JOIN membership_status ms ON c.MbrMembershipStatusID = ms.StatusID 
             WHERE c.MbrID = :mid AND c.Deleted = 0 AND ms.StatusName = 'Active') AS member_ok,
            (SELECT COUNT(*) FROM contribution_type WHERE ContributionTypeID = :tid AND IsActive = 1) AS type_ok,
            (SELECT COUNT(*) FROM payment_method WHERE PaymentMethodID = :pmid AND IsActive = 1) AS payment_ok";

        $params = [
            ':mid' => $memberId,
            ':tid' => $typeId,
            ':pmid' => $paymentMethodId
        ];

        if ($fiscalYearId !== null) {
            $sql .= ", (SELECT COUNT(*) FROM fiscal_year WHERE FiscalYearID = :fyid AND Status = 'Active') AS fiscal_ok";
            $params[':fyid'] = $fiscalYearId;
        }

        return $this->orm->runQuery($sql, $params)[0];
    }
}
