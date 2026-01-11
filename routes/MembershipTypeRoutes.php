<?php

/**
 * Membership Type & Assignment API Routes – v1
 *
 * Complete church membership tier system with lifecycle management:
 *
 * MEMBERSHIP TYPES (e.g., Full Member, Associate, New Convert, Child)
 * • Full CRUD with uniqueness enforcement
 * • Deletion protection when assigned to members
 * • Clean taxonomy for reporting and permissions
 *
 * MEMBER ASSIGNMENTS
 * • One active membership per member at a time (strict rule)
 * • Start/end dates with overlap prevention
 * • Historical tracking for life-stage analysis
 * • Bulk and single retrieval with status filtering
 *
 * Business Rules Enforced:
 * • Only one active membership assignment per member
 * • Cannot assign overlapping date ranges
 * • Cannot delete a type currently in use
 * • End date must be after start date
 *
 * Essential for:
 * • Membership ceremonies & reporting
 * • Voting rights and leadership eligibility
 * • Spiritual growth tracking
 * • Annual church census
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/MembershipType.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class MembershipTypeRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE MEMBERSHIP TYPE
         $method === 'POST' && $path === 'membershiptype/create' => (function () {
            self::authenticate();
            self::authorize('manage_membership_types');

            $payload = self::getPayload();

            $result = MembershipType::create($payload);
            ResponseHelper::created($result, 'Membership type created');
         })(),

         // UPDATE MEMBERSHIP TYPE
         $method === 'POST' && $pathParts[0] === 'membershiptype' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_membership_types');

            $typeId = self::getIdFromPath($pathParts, 2, 'Membership Type ID');

            $payload = self::getPayload();

            $result = MembershipType::update($typeId, $payload);
            ResponseHelper::success($result, 'Membership type updated');
         })(),

         // DELETE MEMBERSHIP TYPE
         $method === 'POST' && $pathParts[0] === 'membershiptype' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_membership_types');

            $typeId = self::getIdFromPath($pathParts, 2, 'Membership Type ID');

            $result = MembershipType::delete($typeId);
            ResponseHelper::success($result, 'Membership type deleted');
         })(),

         // VIEW SINGLE MEMBERSHIP TYPE
         $method === 'GET' && $pathParts[0] === 'membershiptype' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_membership_types');

            $typeId = self::getIdFromPath($pathParts, 2, 'Membership Type ID');

            $type = MembershipType::get($typeId);
            ResponseHelper::success($type);
         })(),

         // LIST ALL MEMBERSHIP TYPES (Paginated + Search)
         $method === 'GET' && $path === 'membershiptype/all' => (function () {
            self::authenticate();
            self::authorize('view_membership_types');

            [$page, $limit] = self::getPagination(10, 100);

            $filters = [];
            if (!empty($_GET['name'])) {
               $filters['name'] = trim($_GET['name']);
            }

            $result = MembershipType::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // ASSIGN MEMBERSHIP TYPE TO MEMBER
         $method === 'POST' && $pathParts[0] === 'membershiptype' && ($pathParts[1] ?? '') === 'assign' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_membership_types');

            $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

            $payload = self::getPayload();

            $result = MembershipType::assign($memberId, $payload);
            ResponseHelper::success($result, 'Membership type assigned');
         })(),

         // UPDATE MEMBERSHIP ASSIGNMENT (e.g., set end date)
         $method === 'POST' && $pathParts[0] === 'membershiptype' && ($pathParts[1] ?? '') === 'updateAssignment' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_membership_types');

            $assignmentId = self::getIdFromPath($pathParts, 2, 'Assignment ID');

            $payload = self::getPayload();

            $result = MembershipType::updateAssignment($assignmentId, $payload);
            ResponseHelper::success($result, 'Membership assignment updated');
         })(),

         // GET MEMBER'S MEMBERSHIP HISTORY
         $method === 'GET' && $pathParts[0] === 'membershiptype' && ($pathParts[1] ?? '') === 'memberassignments' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_membership_types');

            $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

            $filters = [];
            if (isset($_GET['active']) && $_GET['active'] === 'true') {
               $filters['active'] = true;
            }
            if (!empty($_GET['start_date'])) {
               $filters['start_date'] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
               $filters['end_date'] = $_GET['end_date'];
            }

            $result = MembershipType::getMemberAssignments($memberId, $filters);
            ResponseHelper::success($result);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('MembershipType endpoint not found'),
      };
   }
}

// Dispatch
MembershipTypeRoutes::handle();
