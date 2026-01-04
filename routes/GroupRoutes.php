<?php

/**
 * Group & GroupType API Routes – v1
 *
 * Complete ministry group management system:
 *
 * CHURCH GROUPS (e.g., Choir, Youth, Ushering, Media Team)
 * • Full lifecycle: create → update → delete
 * • Automatic leader membership on creation
 * • Membership management (add/remove members)
 * • Paginated listing with powerful filtering
 * • Role-aware retrieval with member count
 *
 * GROUP TYPES (e.g., Worship, Service, Fellowship)
 * • Simple taxonomy management
 * • Uniqueness enforcement
 * • Deletion protection when in use
 *
 * Business Rules:
 * • Group leader is automatically added as first member
 * • Cannot remove the group leader via membership endpoint
 * • Cannot delete a group with members or messages
 * • Cannot delete a group type currently assigned to groups
 *
 * Essential for organizing volunteers, discipleship, and ministry coordination.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Group.php';
require_once __DIR__ . '/../core/GroupType.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class GroupRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // =================================================================
         // CHURCH GROUPS
         // =================================================================

         // CREATE GROUP
         $method === 'POST' && $path === 'group/create' => (function () {
            self::authenticate();
            self::authorize('manage_groups');

            $payload = self::getPayload();

            $result = Group::create($payload);
            ResponseHelper::created($result, 'Group created');
         })(),

         // UPDATE GROUP
         $method === 'PUT' && $pathParts[0] === 'group' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_groups');

            $groupId = self::getIdFromPath($pathParts, 2, 'Group ID');

            $payload = self::getPayload();

            $result = Group::update($groupId, $payload);
            ResponseHelper::success($result, 'Group updated');
         })(),

         // DELETE GROUP
         $method === 'DELETE' && $pathParts[0] === 'group' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_groups');

            $groupId = self::getIdFromPath($pathParts, 2, 'Group ID');

            $result = Group::delete($groupId);
            ResponseHelper::success($result, 'Group deleted');
         })(),

         // VIEW SINGLE GROUP
         $method === 'GET' && $pathParts[0] === 'group' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_groups');

            $groupId = self::getIdFromPath($pathParts, 2, 'Group ID');

            $group = Group::get($groupId);
            ResponseHelper::success($group);
         })(),

         // LIST ALL GROUPS (Paginated + Multi-Filter)
         $method === 'GET' && $path === 'group/all' => (function () {
            self::authenticate();
            self::authorize('view_groups');

            [$page, $limit] = self::getPagination(10, 100);

            $filters = [];
            if (!empty($_GET['type_id']) && is_numeric($_GET['type_id'])) {
               $filters['type_id'] = (int)$_GET['type_id'];
            }
            if (!empty($_GET['branch_id']) && is_numeric($_GET['branch_id'])) {
               $filters['branch_id'] = (int)$_GET['branch_id'];
            }
            if (!empty($_GET['name'])) {
               $filters['name'] = trim($_GET['name']);
            }

            $result = Group::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // ADD MEMBER TO GROUP
         $method === 'POST' && $pathParts[0] === 'group' && ($pathParts[1] ?? '') === 'addMember' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_groups');

            $groupId = self::getIdFromPath($pathParts, 2, 'Group ID');

            $payload = self::getPayload([
               'member_id' => 'required|numeric'
            ]);

            $result = Group::addMember($groupId, (int)$payload['member_id']);
            ResponseHelper::success($result, 'Member added to group');
         })(),

         // REMOVE MEMBER FROM GROUP
         $method === 'DELETE' && $pathParts[0] === 'group' && ($pathParts[1] ?? '') === 'removeMember' && isset($pathParts[2], $pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_groups');

            $groupId = self::getIdFromPath($pathParts, 2, 'Group ID');
            $memberId = self::getIdFromPath($pathParts, 3, 'Member ID');

            $result = Group::removeMember($groupId, $memberId);
            ResponseHelper::success($result, 'Member removed from group');
         })(),

         // GET GROUP MEMBERS (Paginated)
         $method === 'GET' && $pathParts[0] === 'group' && ($pathParts[1] ?? '') === 'members' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_groups');

            $groupId = self::getIdFromPath($pathParts, 2, 'Group ID');

            [$page, $limit] = self::getPagination(10, 100);

            $result = Group::getMembers($groupId, $page, $limit);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // =================================================================
         // GROUP TYPES
         // =================================================================

         // CREATE GROUP TYPE
         $method === 'POST' && $path === 'grouptype/create' => (function () {
            self::authenticate();
            self::authorize('manage_group_types');

            $payload = self::getPayload([
               'name' => 'required|max:100'
            ]);

            $result = GroupType::create($payload);
            ResponseHelper::created($result, 'Group type created');
         })(),

         // UPDATE GROUP TYPE
         $method === 'PUT' && $pathParts[0] === 'grouptype' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_group_types');

            $typeId = self::getIdFromPath($pathParts, 2, 'GroupType ID');

            $payload = self::getPayload();

            $result = GroupType::update($typeId, $payload);
            ResponseHelper::success($result, 'Group type updated');
         })(),

         // DELETE GROUP TYPE
         $method === 'DELETE' && $pathParts[0] === 'grouptype' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_group_types');

            $typeId = self::getIdFromPath($pathParts, 2, 'GroupType ID');

            $result = GroupType::delete($typeId);
            ResponseHelper::success($result, 'Group type deleted');
         })(),

         // VIEW SINGLE GROUP TYPE
         $method === 'GET' && $pathParts[0] === 'grouptype' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_group_types');

            $typeId = self::getIdFromPath($pathParts, 2, 'GroupType ID');

            $type = GroupType::get($typeId);
            ResponseHelper::success($type);
         })(),

         // LIST ALL GROUP TYPES
         $method === 'GET' && $path === 'grouptype/all' => (function () {
            self::authenticate();
            self::authorize('view_group_types');

            [$page, $limit] = self::getPagination(10, 100);

            $result = GroupType::getAll($page, $limit);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Group/GroupType endpoint not found'),
      };
   }
}

// Dispatch
GroupRoutes::handle();
