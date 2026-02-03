<?php

declare(strict_types=1);

namespace AliveChMS\Core\Identity;

use AliveChMS\Core\System\Database;
use AliveChMS\Core\System\ORM;
use Exception;
use PDO;

/**
 * RBAC Repository
 * 
 * Handles data access for Roles, Permissions, and User Role Assignments.
 */
class RBACRepository
{
    private ORM $orm;
    private PDO $db;

    public function __construct()
    {
        $this->orm = new ORM();
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserPermissions(int $userId): array
    {
        $stmt = $this->db->prepare("CALL sp_get_user_permissions(:user_id)");
        $stmt->execute([':user_id' => $userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt->closeCursor();
        return $permissions ?: [];
    }

    public function getUserRoles(int $userId): array
    {
        return $this->orm->runQuery("
            SELECT cr.*, mr.StartDate, mr.EndDate
            FROM member_role mr
            JOIN church_role cr ON mr.RoleID = cr.RoleID
            WHERE mr.MbrID = :uid AND mr.IsActive = 1 AND cr.IsActive = 1
            ORDER BY cr.DisplayOrder",
            [':uid' => $userId]
        );
    }

    public function findRoleById(int $id): ?array
    {
        $res = $this->orm->getWhere('church_role', ['RoleID' => $id, 'IsActive' => 1]);
        return $res[0] ?? null;
    }

    public function assignRole(array $data): int
    {
        $result = $this->orm->insert('member_role', $data);
        return (int) $result['id'];
    }

    public function removeRole(int $userId, int $roleId): int
    {
        return $this->orm->update('member_role', [
            'IsActive' => 0,
            'EndDate' => date('Y-m-d')
        ], ['MbrID' => $userId, 'RoleID' => $roleId]);
    }

    public function getPermissionsGrouped(): array
    {
        return $this->orm->runQuery("
            SELECT pc.CategoryName, pc.CategoryDescription, p.*
            FROM permission p
            LEFT JOIN permission_category pc ON p.CategoryID = pc.CategoryID
            ORDER BY pc.DisplayOrder, p.PermissionName"
        );
    }

    /* Role Management */

    public function createRole(array $data): int
    {
        $result = $this->orm->insert('church_role', $data);
        return (int) $result['id'];
    }

    public function updateRole(int $id, array $data): int
    {
        return $this->orm->update('church_role', $data, ['RoleID' => $id]);
    }

    public function deleteRole(int $id): int
    {
        return $this->orm->delete('church_role', ['RoleID' => $id]);
    }

    public function findRoleByName(string $name): ?array
    {
        $res = $this->orm->getWhere('church_role', ['RoleName' => $name]);
        return $res[0] ?? null;
    }

    public function getRolePermissions(int $roleId): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'role_permission rp',
            joins: [['table' => 'permission p', 'on' => 'rp.PermissionID = p.PermissionID']],
            fields: ['p.*'],
            conditions: ['rp.RoleID' => ':rid'],
            params: [':rid' => $roleId]
        );
    }

    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->orm->beginTransaction();
        try {
            $this->orm->delete('role_permission', ['RoleID' => $roleId]);
            foreach ($permissionIds as $pid) {
                $this->orm->insert('role_permission', ['RoleID' => $roleId, 'PermissionID' => $pid]);
            }
            $this->orm->commit();
        } catch (Exception $e) {
            $this->orm->rollBack();
            throw $e;
        }
    }

    public function getAllRoles(): array
    {
        return $this->orm->getWhere('church_role', ['IsActive' => 1]);
    }

    /* Permission Management */

    public function createPermission(array $data): int
    {
        $result = $this->orm->insert('permission', $data);
        return (int) $result['id'];
    }

    public function updatePermission(int $id, array $data): int
    {
        return $this->orm->update('permission', $data, ['PermissionID' => $id]);
    }

    public function deletePermission(int $id): int
    {
        return $this->orm->delete('permission', ['PermissionID' => $id]);
    }

    public function findPermissionById(int $id): ?array
    {
        $res = $this->orm->getWhere('permission', ['PermissionID' => $id]);
        return $res[0] ?? null;
    }

    public function findPermissionByName(string $name): ?array
    {
        $res = $this->orm->getWhere('permission', ['PermissionName' => $name]);
        return $res[0] ?? null;
    }

    public function isPermissionInUse(int $id): bool
    {
        $res = $this->orm->getWhere('role_permission', ['PermissionID' => $id]);
        return !empty($res);
    }

    public function getAllPermissions(): array
    {
        return $this->orm->getWhere('permission', ['IsActive' => 1]);
    }
}
