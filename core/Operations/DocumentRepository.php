<?php

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\System\ORM;

/**
 * Document Repository
 * 
 * Handles database operations for system documents and categories.
 */
class DocumentRepository
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
        $result = $this->orm->insert('document', $data);
        return (int)$result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update('document', $data, ['DocumentID' => $id]);
    }

    public function delete(int $id): int
    {
        return $this->orm->delete('document', ['DocumentID' => $id]);
    }

    public function findById(int $id): ?array
    {
        $result = $this->orm->selectWithJoin(
            baseTable: 'document d',
            joins: [
                ['table' => 'document_category dc', 'on' => 'd.CategoryID = dc.CategoryID', 'type' => 'LEFT'],
                ['table' => 'branch b',            'on' => 'd.BranchID = b.BranchID', 'type' => 'LEFT'],
                ['table' => 'churchmember m',      'on' => 'd.UploadedBy = m.MbrID', 'type' => 'LEFT']
            ],
            fields: [
               'd.*', 'dc.CategoryName', 'b.BranchName', 
               'm.MbrFirstName AS UploaderFirstName', 'm.MbrFamilyName AS UploaderLastName'
            ],
            conditions: ['d.DocumentID' => ':id'],
            params: [':id' => $id]
        );

        return $result[0] ?? null;
    }

    public function findAll(int $limit, int $offset, array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['branch_id'])) {
            $conditions['d.BranchID'] = ':branch_id';
            $params[':branch_id'] = (int)$filters['branch_id'];
        }
        if (!empty($filters['category_id'])) {
            $conditions['d.CategoryID'] = ':cat_id';
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        $documents = $this->orm->selectWithJoin(
            baseTable: 'document d',
            joins: [['table' => 'document_category dc', 'on' => 'd.CategoryID = dc.CategoryID', 'type' => 'LEFT']],
            fields: ['d.DocumentID', 'd.DocumentName', 'd.FileURL', 'd.FileSize', 'd.FileType', 'd.UploadedAt', 'dc.CategoryName'],
            conditions: $conditions,
            params: $params,
            orderBy: ['d.UploadedAt' => 'DESC'],
            limit: $limit,
            offset: $offset
        );

        $totalCountParams = [];
        $whereSql = "";
        if (!empty($conditions)) {
            $parts = [];
            foreach ($conditions as $col => $placeholder) {
                $parts[] = "$col = $placeholder";
            }
            $whereSql = "WHERE " . implode(' AND ', $parts);
            $totalCountParams = $params;
        }

        $total = $this->orm->runQuery("SELECT COUNT(*) AS total FROM document d $whereSql", $totalCountParams)[0]['total'];

        return ['data' => $documents, 'total' => (int)$total];
    }

    public function isValidCategory(int $id): bool
    {
        return !empty($this->orm->getWhere('document_category', ['CategoryID' => $id, 'IsActive' => 1]));
    }

    public function findByCategory(int $categoryId, int $limit, int $offset): array
    {
        $documents = $this->orm->selectWithJoin(
            baseTable: 'document d',
            joins: [['table' => 'document_category dc', 'on' => 'd.CategoryID = dc.CategoryID', 'type' => 'LEFT']],
            fields: ['d.DocumentID', 'd.DocumentName', 'd.FileURL', 'd.FileSize', 'd.FileType', 'd.UploadedAt', 'dc.CategoryName'],
            conditions: ['d.CategoryID' => ':cat_id'],
            params: [':cat_id' => $categoryId],
            orderBy: ['d.UploadedAt' => 'DESC'],
            limit: $limit,
            offset: $offset
        );

        $total = $this->orm->runQuery("SELECT COUNT(*) AS total FROM document d WHERE d.CategoryID = :cat_id", [':cat_id' => $categoryId])[0]['total'];

        return ['data' => $documents, 'total' => (int) $total];
    }

    public function findByMember(int $memberId, int $limit, int $offset): array
    {
        $documents = $this->orm->selectWithJoin(
            baseTable: 'document d',
            joins: [['table' => 'document_category dc', 'on' => 'd.CategoryID = dc.CategoryID', 'type' => 'LEFT']],
            fields: ['d.DocumentID', 'd.DocumentName', 'd.FileURL', 'd.FileSize', 'd.FileType', 'd.UploadedAt', 'dc.CategoryName'],
            conditions: ['d.UploadedBy' => ':member_id'],
            params: [':member_id' => $memberId],
            orderBy: ['d.UploadedAt' => 'DESC'],
            limit: $limit,
            offset: $offset
        );

        $total = $this->orm->runQuery("SELECT COUNT(*) AS total FROM document d WHERE d.UploadedBy = :member_id", [':member_id' => $memberId])[0]['total'];

        return ['data' => $documents, 'total' => (int) $total];
    }
}
