<?php

/**
 * Milestone API Routes – v1
 *
 * Complete milestone management:
 * - Record member milestones (baptism, marriage, salvation, etc.)
 * - Manage milestone types
 * - Statistics and reporting
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/MemberMilestone.php';
require_once __DIR__ . '/../core/MilestoneType.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class MilestoneRoutes extends BaseRoute
{
   public static function handle(): void
   {
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // ========== MILESTONE CRUD ==========

         // CREATE MILESTONE
         $method === 'POST' && $path === 'milestone/create' => (function () {
            self::authenticate();
            self::authorize('members.edit');
            $payload = self::getPayload();
            $result = MemberMilestone::create($payload);
            ResponseHelper::created($result, 'Milestone recorded');
         })(),

         // UPDATE MILESTONE
         $method === 'PUT' && $pathParts[0] === 'milestone' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('members.edit');
            $milestoneId = self::getIdFromPath($pathParts, 2, 'Milestone ID');
            $payload = self::getPayload();
            $result = MemberMilestone::update($milestoneId, $payload);
            ResponseHelper::success($result, 'Milestone updated');
         })(),

         // DELETE MILESTONE
         $method === 'DELETE' && $pathParts[0] === 'milestone' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('members.edit');
            $milestoneId = self::getIdFromPath($pathParts, 2, 'Milestone ID');
            $result = MemberMilestone::delete($milestoneId);
            ResponseHelper::success($result, 'Milestone deleted');
         })(),

         // VIEW SINGLE MILESTONE
         $method === 'GET' && $pathParts[0] === 'milestone' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('members.view');
            $milestoneId = self::getIdFromPath($pathParts, 2, 'Milestone ID');
            $milestone = MemberMilestone::get($milestoneId);
            ResponseHelper::success($milestone);
         })(),

         // LIST ALL MILESTONES
         $method === 'GET' && $path === 'milestone/all' => (function () {
            self::authenticate();
            self::authorize('members.view');
            [$page, $limit] = self::getPagination(25, 100);
            $filters = self::getFilters(['member_id', 'milestone_type_id', 'year', 'start_date', 'end_date', 'search']);
            [$sortBy, $sortDir] = self::getSorting('MilestoneDate', 'DESC', ['MilestoneDate', 'MemberName', 'MilestoneTypeName']);
            $filters['sort_by'] = $sortBy;
            $filters['sort_dir'] = $sortDir;
            $result = MemberMilestone::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // GET MILESTONE STATISTICS
         $method === 'GET' && $path === 'milestone/stats' => (function () {
            self::authenticate();
            self::authorize('members.view');
            $year = !empty($_GET['year']) ? (int)$_GET['year'] : null;
            $result = MemberMilestone::getStats($year);
            ResponseHelper::success($result);
         })(),

         // GET MILESTONES BY MEMBER
         $method === 'GET' && $pathParts[0] === 'milestone' && ($pathParts[1] ?? '') === 'member' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('members.view');
            $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');
            $result = MemberMilestone::getByMember($memberId);
            ResponseHelper::success($result['data']);
         })(),

         // ========== MILESTONE TYPES ==========

         // LIST MILESTONE TYPES
         $method === 'GET' && $path === 'milestone/types' => (function () {
            self::authenticate();
            self::authorize('members.view');
            $activeOnly = isset($_GET['active']) && $_GET['active'] === '1';
            $result = MilestoneType::getAll($activeOnly);
            ResponseHelper::success($result['data']);
         })(),

         // CREATE MILESTONE TYPE
         $method === 'POST' && $path === 'milestone/type/create' => (function () {
            self::authenticate();
            self::authorize('settings.edit');
            $payload = self::getPayload();
            $result = MilestoneType::create($payload);
            ResponseHelper::created($result, 'Milestone type created');
         })(),

         // UPDATE MILESTONE TYPE
         $method === 'PUT' && $pathParts[0] === 'milestone' && ($pathParts[1] ?? '') === 'type' && ($pathParts[2] ?? '') === 'update' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('settings.edit');
            $typeId = self::getIdFromPath($pathParts, 3, 'Milestone Type ID');
            $payload = self::getPayload();
            $result = MilestoneType::update($typeId, $payload);
            ResponseHelper::success($result, 'Milestone type updated');
         })(),

         // DELETE MILESTONE TYPE
         $method === 'DELETE' && $pathParts[0] === 'milestone' && ($pathParts[1] ?? '') === 'type' && ($pathParts[2] ?? '') === 'delete' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('settings.edit');
            $typeId = self::getIdFromPath($pathParts, 3, 'Milestone Type ID');
            $result = MilestoneType::delete($typeId);
            ResponseHelper::success($result, 'Milestone type deleted');
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Milestone endpoint not found'),
      };
   }
}

MilestoneRoutes::handle();
