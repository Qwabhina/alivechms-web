<?php

/**
 * Role & Permission Management API Routes – v1
 *
 * The beating heart of AliveChMS Role-Based Access Control (RBAC):
 *
 * ROLES (e.g., Pastor, Elder, Treasurer, Admin, Member, Guest)
 * • Full lifecycle: create → update → delete (with safety)
 * • Bulk permission assignment (atomic replace-all)
 * • Role-to-member assignment
 * • Rich retrieval with full permission tree
 *
 * PERMISSIONS
 * • Managed via Permission.php (separate file)
 * • Atomic building blocks of authority
 *
 * Business & Spiritual Governance:
 * • Deletion blocked if role assigned to any member
 * • Permission changes are immediate and system-wide
 * • Full audit trail via logs
 * • Designed for stewardship, accountability, and biblical leadership structure
 *
 * Critical for:
 * • Protecting financial data
 * • Safeguarding member privacy
 * • Enforcing leadership hierarchy
 * • Preventing unauthorized access
 *
 * "Let every person be subject to the governing authorities..." — Romans 13:1
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Role.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class RoleRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE ROLE
         $method === 'POST' && $path === 'role/create' => (function () {
            self::authenticate();
            self::authorize('manage_roles');

            $payload = self::getPayload();

            $result = Role::create($payload);
            ResponseHelper::created($result, 'Role created');
         })(),

         // UPDATE ROLE
         $method === 'PUT' && $pathParts[0] === 'role' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_roles');

            $roleId = self::getIdFromPath($pathParts, 2, 'Role ID');

            $payload = self::getPayload();

            $result = Role::update($roleId, $payload);
            ResponseHelper::success($result, 'Role updated');
         })(),

         // DELETE ROLE
         $method === 'DELETE' && $pathParts[0] === 'role' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_roles');

            $roleId = self::getIdFromPath($pathParts, 2, 'Role ID');

            $result = Role::delete($roleId);
            ResponseHelper::success($result, 'Role deleted');
         })(),

         // VIEW SINGLE ROLE (with full permission tree)
         $method === 'GET' && $pathParts[0] === 'role' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_roles');

            $roleId = self::getIdFromPath($pathParts, 2, 'Role ID');

            $role = Role::get($roleId);
            ResponseHelper::success($role);
         })(),

         // LIST ALL ROLES (with permissions) - Requires authentication
         $method === 'GET' && $path === 'role/all' => (function () {
            self::authenticate(); // SECURITY FIX: Require authentication
            // No specific permission required - any authenticated user can see roles for dropdowns

            $result = Role::getAll();
            ResponseHelper::success($result);
         })(),

         // LIST ROLE NAMES ONLY (for dropdowns) - Public endpoint
         $method === 'GET' && $path === 'role/names' => (function () {
            self::authenticate(false); // Public access for dropdowns

            $orm = new ORM();
            $roles = $orm->runQuery("SELECT RoleID, RoleName FROM churchrole ORDER BY RoleName ASC", []);

            ResponseHelper::success($roles);
         })(),

         // ASSIGN PERMISSIONS TO ROLE (Replace All)
         $method === 'POST' && $pathParts[0] === 'role' && ($pathParts[1] ?? '') === 'permissions' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_roles');

            $roleId = self::getIdFromPath($pathParts, 2, 'Role ID');

            $payload = self::getPayload([
               'permission_ids' => 'required|array'
            ]);

            $result = Role::assignPermissions($roleId, $payload['permission_ids']);
            ResponseHelper::success($result, 'Permissions assigned to role');
         })(),

         // ASSIGN ROLE TO MEMBER
         $method === 'POST' && $pathParts[0] === 'role' && ($pathParts[1] ?? '') === 'assign' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_roles');

            $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

            $payload = self::getPayload([
               'role_id' => 'required|numeric'
            ]);

            $result = Role::assignToMember($memberId, (int)$payload['role_id']);
            ResponseHelper::success($result, 'Role assigned to member');
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Role endpoint not found'),
      };
   }
}

// Dispatch
RoleRoutes::handle();
