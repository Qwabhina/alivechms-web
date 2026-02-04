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

    public function getAll(int $page = 1, int $limit = 25, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        $whereConditions = ['mm.Deleted = 0'];
        $params = [];

        if (!empty($filters['type'])) {
            $whereConditions[] = 'mm.MilestoneTypeID = :type';
            $params[':type'] = (int) $filters['type'];
        }

        if (!empty($filters['member_id'])) {
            $whereConditions[] = 'mm.MbrID = :member_id';
            $params[':member_id'] = (int) $filters['member_id'];
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'mm.MilestoneDate >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'mm.MilestoneDate <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $whereConditions[] = '(m.MbrFirstName LIKE :search OR m.MbrFamilyName LIKE :search2 OR m.MbrOtherNames LIKE :search3 OR mt.TypeName LIKE :search4)';
            $params[':search'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
        }

        $whereClause = implode(' AND ', $whereConditions);

        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $milestones = $this->orm->runQuery(
            "SELECT mm.*, mt.TypeName AS MilestoneTypeName, m.MbrFirstName, m.MbrFamilyName, m.MbrOtherNames
             FROM member_milestone mm
             LEFT JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
             LEFT JOIN churchmember m ON mm.MbrID = m.MbrID
             WHERE $whereClause
             ORDER BY mm.MilestoneDate DESC
             LIMIT :limit OFFSET :offset",
            $params
        );

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS count
             FROM member_milestone mm
             LEFT JOIN milestone_type mt ON mm.MilestoneTypeID = mt.MilestoneTypeID
             LEFT JOIN churchmember m ON mm.MbrID = m.MbrID
             WHERE $whereClause",
            $params
        )[0]['count'];

        return [
            'data' => $milestones,
            'pagination' => [
                'total' => (int) $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($total / $limit)
            ]
        ];
    }
}
