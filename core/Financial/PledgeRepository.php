<?php

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\System\ORM;

/**
 * Pledge Repository
 * 
 * Handles database operations for church pledges and payments.
 * Encapsulates complex financial reporting and fulfillment logic.
 */
class PledgeRepository
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
        $result = $this->orm->insert('pledge', $data);
        return (int)$result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update('pledge', $data, ['PledgeID' => $id]);
    }

    public function findById(int $id): ?array
    {
        $result = $this->orm->runQuery(
            "SELECT p.*, 
                    m.MbrFirstName, m.MbrFamilyName,
                    pt.PledgeTypeName,
                    fy.FiscalYearName,
                    c.MbrFirstName AS CreatorFirstName, c.MbrFamilyName AS CreatorFamilyName
             FROM pledge p
             JOIN churchmember m ON p.MbrID = m.MbrID
             JOIN pledge_type pt ON p.PledgeTypeID = pt.PledgeTypeID
             LEFT JOIN fiscal_year fy ON p.FiscalYearID = fy.FiscalYearID
             LEFT JOIN churchmember c ON p.CreatedBy = c.MbrID
             WHERE p.PledgeID = :id",
            [':id' => $id]
        );

        return $result[0] ?? null;
    }

    public function findPledgesByMember(int $memberId): array
    {
        return $this->orm->getWhere('pledge', ['MbrID' => $memberId]);
    }

    public function createPayment(array $data): int
    {
        $result = $this->orm->insert('pledge_payment', $data);
        return (int)$result['id'];
    }

    public function getPayments(int $pledgeId): array
    {
        return $this->orm->runQuery(
            "SELECT pp.*, r.MbrFirstName AS RecorderFirstName, r.MbrFamilyName AS RecorderFamilyName
             FROM pledge_payment pp
             LEFT JOIN churchmember r ON pp.RecordedBy = r.MbrID
             WHERE pp.PledgeID = :id
             ORDER BY pp.PaymentDate DESC",
            [':id' => $pledgeId]
        );
    }

    public function getTotalPaid(int $pledgeId): float
    {
        $res = $this->orm->runQuery(
            "SELECT COALESCE(SUM(PaymentAmount), 0) AS paid FROM pledge_payment WHERE PledgeID = :id",
            [':id' => $pledgeId]
        );
        return (float)$res[0]['paid'];
    }

    public function findAll(int $limit, int $offset, array $filters = [], string $orderBy = 'p.PledgeDate DESC'): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['member_id'])) {
            $where[] = 'p.MbrID = :mid';
            $params[':mid'] = (int)$filters['member_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.PledgeStatus = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['fiscal_year_id'])) {
            $where[] = 'p.FiscalYearID = :fy';
            $params[':fy'] = (int)$filters['fiscal_year_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $pledges = $this->orm->runQuery(
            "SELECT p.PledgeID, p.PledgeAmount, p.PledgeDate, p.DueDate, p.PledgeStatus, p.Description,
                    p.MbrID, m.MbrFirstName, m.MbrFamilyName,
                    pt.PledgeTypeName,
                    fy.FiscalYearName,
                    COALESCE((SELECT SUM(PaymentAmount) FROM pledge_payment WHERE PledgeID = p.PledgeID), 0) AS TotalPaid
             FROM pledge p
             JOIN churchmember m ON p.MbrID = m.MbrID
             JOIN pledge_type pt ON p.PledgeTypeID = pt.PledgeTypeID
             LEFT JOIN fiscal_year fy ON p.FiscalYearID = fy.FiscalYearID
             $whereClause
             ORDER BY $orderBy
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset])
        );

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM pledge p $whereClause",
            $params
        )[0]['total'];

        return [
            'data' => $pledges,
            'total' => (int)$total
        ];
    }

    public function getStats(?int $fyId = null): array
    {
        $fyCondition = $fyId ? "AND p.FiscalYearID = :fy_id" : "";
        $fyCondition2 = $fyId ? "AND p2.FiscalYearID = :fy_id2" : "";
        $params = $fyId ? [':fy_id' => $fyId] : [];
        $extendedParams = $fyId ? array_merge($params, [':fy_id2' => $fyId]) : [];

        // Complex stats from Pledge.php refactored into repository
        $stats = [
            'total'     => $this->orm->runQuery("SELECT COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count FROM pledge p WHERE 1=1 $fyCondition", $params)[0],
            'active'    => $this->orm->runQuery("SELECT COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count FROM pledge p WHERE PledgeStatus = 'Active' $fyCondition", $params)[0],
            'fulfilled' => $this->orm->runQuery("SELECT COALESCE(SUM(PledgeAmount), 0) AS total, COUNT(*) AS count FROM pledge p WHERE PledgeStatus = 'Fulfilled' $fyCondition", $params)[0],
            'payments'  => $this->orm->runQuery("SELECT COALESCE(SUM(pp.PaymentAmount), 0) AS total, COUNT(*) AS count FROM pledge_payment pp JOIN pledge p ON pp.PledgeID = p.PledgeID WHERE 1=1 $fyCondition", $params)[0],
            'outstanding' => $this->orm->runQuery(
                "SELECT COALESCE(SUM(p.PledgeAmount), 0) - COALESCE((
                    SELECT SUM(pp.PaymentAmount) FROM pledge_payment pp 
                    JOIN pledge p2 ON pp.PledgeID = p2.PledgeID 
                    WHERE p2.PledgeStatus = 'Active' $fyCondition2
                 ), 0) AS outstanding
                 FROM pledge p WHERE p.PledgeStatus = 'Active' $fyCondition",
                $extendedParams
            )[0]['outstanding']
        ];

        return $stats;
    }

    public function isValidPledgeType(int $id): bool
    {
        return !empty($this->orm->getWhere('pledge_type', ['PledgeTypeID' => $id, 'IsActive' => 1]));
    }
}
