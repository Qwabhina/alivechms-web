<?php

declare(strict_types=1);

namespace AliveChMS\Core\Identity;

use AliveChMS\Core\System\ORM;

/**
 * Auth Repository
 * 
 * Handles database operations for user authentication, accounts, and sessions.
 */
class AuthRepository
{
    private ORM $orm;

    public function __construct()
    {
        $this->orm = new ORM();
    }

    public function findUserByUsername(string $username): ?array
    {
        $result = $this->orm->selectWithJoin(
            baseTable: 'user_authentication u',
            joins: [
                ['table' => 'churchmember c', 'on' => 'u.MbrID = c.MbrID'],
                ['table' => 'membership_status ms', 'on' => 'c.MbrMembershipStatusID = ms.StatusID']
            ],
            fields: ['u.*', 'c.*', 'ms.StatusName as MembershipStatus'],
            conditions: ['u.Username' => ':username', 'c.Deleted' => 0],
            params: [':username' => $username]
        );

        return $result[0] ?? null;
    }

    public function updateLoginMetadata(int $authUserId, array $data): int
    {
        return $this->orm->update('user_authentication', $data, ['UserID' => $authUserId]);
    }

    public function createSession(array $data): int
    {
        $result = $this->orm->insert('user_sessions', $data);
        return (int) $result['id'];
    }

    public function findSessionByHash(string $hash): ?array
    {
        $res = $this->orm->getWhere('user_sessions', ['TokenHash' => $hash, 'IsRevoked' => 0]);
        return $res[0] ?? null;
    }

    public function findSessionById(int $sessionId): ?array
    {
        $res = $this->orm->getWhere('user_sessions', ['SessionID' => $sessionId]);
        return $res[0] ?? null;
    }

    public function revokeSession(int $sessionId): int
    {
        return $this->orm->update('user_sessions', [
            'IsRevoked' => 1,
            'RevokedAt' => date('Y-m-d H:i:s')
        ], ['SessionID' => $sessionId]);
    }

    public function revokeSessionsByUserId(int $userId, ?string $exceptTokenHash = null): int
    {
        $conditions = "UserID = :user_id AND IsRevoked = 0";
        $params = [':user_id' => $userId];

        if ($exceptTokenHash) {
            $conditions .= " AND TokenHash != :hash";
            $params[':hash'] = $exceptTokenHash;
        }

        return (int) $this->orm->runQuery(
            "UPDATE user_sessions SET IsRevoked = 1, RevokedAt = NOW() WHERE $conditions",
            $params
        );
    }

    public function cleanupExpiredSessions(int $daysBack = 7): int
    {
        return (int) $this->orm->runQuery(
            "DELETE FROM user_sessions WHERE ExpiresAt < DATE_SUB(NOW(), INTERVAL :days DAY)",
            [':days' => $daysBack]
        );
    }

    public function getUserSessions(int $userId): array
    {
        return $this->orm->getWhere('user_sessions', [
            'UserID' => $userId,
            'IsRevoked' => 0,
            'ExpiresAt >' => date('Y-m-d H:i:s')
        ]);
    }
}
