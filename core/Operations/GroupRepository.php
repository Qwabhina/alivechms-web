<?php

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\System\ORM;

/**
 * Group Repository
 * 
 * Handles database operations for church groups and members.
 */
class GroupRepository
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
        $result = $this->orm->insert('church_group', $data);
        return (int)$result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update('church_group', $data, ['GroupID' => $id]);
    }

    public function findById(int $id): ?array
    {
        $result = $this->orm->selectWithJoin(
            baseTable: 'church_group g',
            joins: [
                ['table' => 'churchmember l', 'on' => 'g.GroupLeaderID = l.MbrID'],
                ['table' => 'group_type t',   'on' => 'g.GroupTypeID = t.GroupTypeID']
            ],
            fields: [
                'g.*',
                'l.MbrFirstName AS LeaderFirstName',
                'l.MbrFamilyName AS LeaderFamilyName',
                't.GroupTypeName',
                '(SELECT COUNT(*) FROM group_member gm WHERE gm.GroupID = g.GroupID) AS MemberCount'
            ],
            conditions: ['g.GroupID' => ':id', 'g.Deleted' => 0],
            params: [':id' => $id]
        );

        return $result[0] ?? null;
    }

    public function findAll(int $limit, int $offset, array $filters = []): array
    {
        $conditions = ['g.Deleted' => 0];
        $params = [];

        if (!empty($filters['type_id'])) {
            $conditions['g.GroupTypeID'] = ':type_id';
            $params[':type_id'] = (int)$filters['type_id'];
        }
        if (!empty($filters['name'])) {
            $conditions['g.GroupName LIKE'] = ':name_like';
            $params[':name_like'] = '%' . $filters['name'] . '%';
        }

        $groups = $this->orm->selectWithJoin(
            baseTable: 'church_group g',
            joins: [
                ['table' => 'churchmember l', 'on' => 'g.GroupLeaderID = l.MbrID'],
                ['table' => 'group_type t',   'on' => 'g.GroupTypeID = t.GroupTypeID']
            ],
            fields: [
                'g.GroupID',
                'g.GroupName',
                'g.GroupDescription',
                'g.GroupLeaderID',
                'l.MbrFirstName AS LeaderFirstName',
                'l.MbrFamilyName AS LeaderFamilyName',
                't.GroupTypeName',
                '(SELECT COUNT(*) FROM group_member gm WHERE gm.GroupID = g.GroupID) AS MemberCount'
            ],
            conditions: $conditions,
            params: $params,
            orderBy: ['g.GroupName' => 'ASC'],
            limit: $limit,
            offset: $offset
        );

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM church_group WHERE Deleted = 0",
            $params
        )[0]['total'];

        return [
            'data' => $groups,
            'total' => (int)$total
        ];
    }

    /**
     * Membership
     */

    public function addMember(int $groupId, int $memberId): void
    {
        $this->orm->insert('group_member', [
            'GroupID'  => $groupId,
            'MbrID'    => $memberId,
            'JoinedAt' => date('Y-m-d H:i:s')
        ]);
    }

    public function removeMember(int $groupId, int $memberId): void
    {
        $this->orm->delete('group_member', [
            'GroupID' => $groupId,
            'MbrID'   => $memberId
        ]);
    }

    public function getMembers(int $groupId, int $limit, int $offset): array
    {
        $members = $this->orm->selectWithJoin(
            baseTable: 'group_member gm',
            joins: [['table' => 'churchmember m', 'on' => 'gm.MbrID = m.MbrID']],
            fields: ['m.MbrID', 'm.MbrFirstName', 'm.MbrFamilyName', 'gm.JoinedAt'],
            conditions: ['gm.GroupID' => ':id'],
            params: [':id' => $groupId],
            limit: $limit,
            offset: $offset
        );

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM group_member WHERE GroupID = :id",
            [':id' => $groupId]
        )[0]['total'];

        return [
            'data' => $members,
            'total' => (int)$total
        ];
    }

    public function isMember(int $groupId, int $memberId): bool
    {
        $res = $this->orm->getWhere('group_member', ['GroupID' => $groupId, 'MbrID' => $memberId]);
        return !empty($res);
    }

    public function delete(int $groupId): void
    {
        $this->orm->update('church_group', ['Deleted' => 1], ['GroupID' => $groupId]);
    }
}
