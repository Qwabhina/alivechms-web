<?php

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\System\ORM;

/**
 * Visitor Repository
 * 
 * Handles database operations for church visitors and follow-ups.
 */
class VisitorRepository
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
        $result = $this->orm->insert('visitor', $data);
        return (int)$result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update('visitor', $data, ['VisitorID' => $id]);
    }

    public function delete(int $id): int
    {
        return $this->orm->delete('visitor', ['VisitorID' => $id]);
    }

    public function findById(int $id): ?array
    {
        $result = $this->orm->selectWithJoin(
            baseTable: 'visitor v',
            joins: [
                ['table' => 'branch b',       'on' => 'v.BranchID = b.BranchID', 'type' => 'LEFT'],
                ['table' => 'churchmember m', 'on' => 'v.AssignedFollowUpPerson = m.MbrID', 'type' => 'LEFT']
            ],
            fields: ['v.*', 'b.BranchName', 'm.MbrFirstName AS AssignedFirstName', 'm.MbrFamilyName AS AssignedLastName'],
            conditions: ['v.VisitorID' => ':id'],
            params: [':id' => $id]
        );

        return $result[0] ?? null;
    }

    public function findAll(int $limit, int $offset, array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['branch_id'])) {
            $conditions['v.BranchID'] = ':branch_id';
            $params[':branch_id'] = (int)$filters['branch_id'];
        }

        $visitors = $this->orm->selectWithJoin(
            baseTable: 'visitor v',
            joins: [['table' => 'branch b', 'on' => 'v.BranchID = b.BranchID', 'type' => 'LEFT']],
            fields: ['v.*', 'b.BranchName'],
            conditions: $conditions,
            params: $params,
            orderBy: ['v.FirstVisitDate' => 'DESC'],
            limit: $limit,
            offset: $offset
        );

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM visitor v",
            $params
        )[0]['total'];

        return [
            'data' => $visitors,
            'total' => (int)$total
        ];
    }
}
