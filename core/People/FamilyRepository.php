<?php

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\System\ORM;

/**
 * Family Repository
 * 
 * Handles database operations for church families and household relationships.
 */
class FamilyRepository
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
        $result = $this->orm->insert('family', $data);
        return (int)$result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update('family', $data, ['FamilyID' => $id]);
    }

    public function delete(int $id): int
    {
        return $this->orm->delete('family', ['FamilyID' => $id]);
    }

    public function findById(int $id): ?array
    {
        $result = $this->orm->runQuery(
            "SELECT 
                f.FamilyID,
                f.FamilyName,
                f.HeadOfHouseholdID,
                f.BranchID,
                f.CreatedAt,
                CONCAT(m.MbrFirstName, ' ', m.MbrFamilyName) AS HeadOfHouseholdName
             FROM family f
             LEFT JOIN churchmember m ON f.HeadOfHouseholdID = m.MbrID
             WHERE f.FamilyID = :family_id",
            [':family_id' => $id]
        );

        return $result[0] ?? null;
    }

    public function findAll(int $limit, int $offset, array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['branch_id'])) {
            $where[] = 'f.BranchID = :branch_id';
            $params[':branch_id'] = (int)$filters['branch_id'];
        }

        $whereClause = implode(' AND ', $where);

        $families = $this->orm->runQuery(
            "SELECT 
                f.FamilyID, 
                f.FamilyName, 
                f.HeadOfHouseholdID,
                f.CreatedAt,
                CONCAT(m.MbrFirstName, ' ', m.MbrFamilyName) AS HeadOfHouseholdName,
                (SELECT COUNT(*) FROM churchmember WHERE FamilyID = f.FamilyID AND Deleted = 0) AS MemberCount
             FROM `family` f
             LEFT JOIN churchmember m ON f.HeadOfHouseholdID = m.MbrID
             WHERE {$whereClause}
             ORDER BY f.FamilyName ASC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM `family` f WHERE {$whereClause}",
            $params
        )[0]['total'];

        return [
            'data' => $families,
            'total' => (int)$total
        ];
    }

    public function getMembers(int $familyId): array
    {
        return $this->orm->runQuery(
            "SELECT 
                m.MbrID,
                m.MbrFirstName,
                m.MbrFamilyName,
                m.MbrEmailAddress,
                m.MbrProfilePicture
             FROM churchmember m
             WHERE m.FamilyID = :family_id AND m.Deleted = 0
             ORDER BY m.MbrFirstName, m.MbrFamilyName",
            [':family_id' => $familyId]
        );
    }

    public function hasActiveMembers(int $familyId): bool
    {
        return $this->orm->exists('churchmember', ['FamilyID' => $familyId, 'Deleted' => 0]);
    }

    public function assignMember(int $memberId, ?int $familyId): void
    {
        $this->orm->update('churchmember', ['FamilyID' => $familyId], ['MbrID' => $memberId]);
    }
}
