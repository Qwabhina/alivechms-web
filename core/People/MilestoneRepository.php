<?php

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\System\ORM;

/**
 * Milestone Repository
 * 
 * Handles database operations for member milestones (Baptism, Wedding, etc.).
 */
class MilestoneRepository
{
    private ORM $orm;

    public function __construct()
    {
        $this->orm = new ORM();
    }

    public function create(array $data): int
    {
        $result = $this->orm->insert('member_milestone', $data);
        return (int)$result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update('member_milestone', $data, ['MilestoneID' => $id]);
    }

    public function delete(int $id): int
    {
        return $this->orm->update('member_milestone', ['Deleted' => 1], ['MilestoneID' => $id]);
    }

    public function findById(int $id): ?array
    {
        $result = $this->orm->selectWithJoin(
            baseTable: 'member_milestone mm',
            joins: [
                ['table' => 'milestone_type mt', 'on' => 'mm.MilestoneTypeID = mt.MilestoneTypeID'],
                ['table' => 'churchmember m',  'on' => 'mm.MbrID = m.MbrID']
            ],
            fields: ['mm.*', 'mt.TypeName AS MilestoneTypeName', 'm.MbrFirstName', 'm.MbrFamilyName'],
            conditions: ['mm.MilestoneID' => ':id', 'mm.Deleted' => 0],
            params: [':id' => $id]
        );
        return $result[0] ?? null;
    }

    public function getStats(?int $year = null): array
    {
        $year = $year ?? (int)date('Y');
        return [
            'total' => $this->orm->runQuery("SELECT COUNT(*) AS count FROM member_milestone WHERE Deleted = 0")[0]['count'],
            'year'  => $this->orm->runQuery("SELECT COUNT(*) AS count FROM member_milestone WHERE Deleted = 0 AND YEAR(MilestoneDate) = :y", [':y' => $year])[0]['count']
        ];
    }
}
