<?php

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\System\ORM;

/**
 * Communication Repository
 * 
 * Handles database operations for system communications and delivery tracking.
 */
class CommunicationRepository
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
        $result = $this->orm->insert('communication', $data);
        return (int)$result['id'];
    }

    public function update(int $id, array $data): int
    {
        return $this->orm->update('communication', $data, ['CommID' => $id]);
    }

    public function findById(int $id): ?array
    {
        $res = $this->orm->getWhere('communication', ['CommID' => $id]);
        return $res[0] ?? null;
    }

    public function createDelivery(array $data): void
    {
        $this->orm->insert('communication_delivery', $data);
    }

    public function updateDelivery(int $commId, int $memberId, array $data): int
    {
        return $this->orm->update('communication_delivery', $data, ['CommID' => $commId, 'MbrID' => $memberId]);
    }

    public function getDeliveriesForUser(int $userId, int $limit, int $offset): array
    {
        $notifications = $this->orm->selectWithJoin(
            baseTable: 'communication_delivery cd',
            joins: [['table' => 'communication c', 'on' => 'cd.CommID = c.CommID']],
            fields: [
                'c.CommID',
                'c.Title',
                'c.Message',
                'c.Channel',
                'c.CreatedAt',
                'cd.Status',
                'cd.DeliveredAt'
            ],
            conditions: ['cd.MbrID' => ':user_id'],
            params: [':user_id' => $userId],
            orderBy: ['c.CreatedAt' => 'DESC'],
            limit: $limit,
            offset: $offset
        );

        $total = $this->orm->runQuery(
            "SELECT COUNT(*) AS total FROM communication_delivery WHERE MbrID = :uid",
            [':uid' => $userId]
        )[0]['total'];

        return [
            'data' => $notifications,
            'total' => (int)$total
        ];
    }

    public function getGroupMembers(int $groupId): array
    {
        return $this->orm->runQuery(
            "SELECT MbrID FROM groupmember WHERE GroupID = :gid",
            [':gid' => $groupId]
        );
    }
}
