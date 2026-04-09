<?php

declare(strict_types=1);

namespace AliveChMS\Core\System;

/**
 * Lookup Repository
 * 
 * Generic repository for lookup tables (MembershipType, GroupType, ExpenseCategory).
 */
class LookupRepository
{
    private ORM $orm;
    private string $table;
    private string $pk;

    public function __construct(string $table, string $pk)
    {
        $this->orm = new ORM();
        $this->table = $table;
        $this->pk = $pk;
    }

    public function getAll(bool $activeOnly = true): array
    {
        $conditions = $activeOnly ? ['IsActive' => 1] : [];
        return $this->orm->getWhere($this->table, $conditions);
    }

    public function findById(int $id): ?array
    {
        $res = $this->orm->getWhere($this->table, [$this->pk => $id]);
        return $res[0] ?? null;
    }

    public function create(array $data): int
    {
        $result = $this->orm->insert($this->table, $data);
        return (int) $result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update($this->table, $data, [$this->pk => $id]);
    }

    public function delete(int $id): int
    {
        return $this->orm->update($this->table, ['IsActive' => 0], [$this->pk => $id]);
    }
}
