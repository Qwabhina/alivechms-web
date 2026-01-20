<?php

/**
 * Volunteer Management API Routes – v1
 *
 * Complete volunteer coordination system for the Body of Christ:
 *
 * VOLUNTEER ROLES (e.g., Usher, Greeter, Sound Tech, Children's Church)
 * • Global reusable role taxonomy
 * • Full CRUD with safety protection
 *
 * EVENT-BASED VOLUNTEER ASSIGNMENTS
 * • Bulk assignment with role & notes
 * • Self-confirmation / decline workflow
 * • Completion marking after service
 * • Full audit trail and status tracking
 *
 * Business & Spiritual Purpose:
 * • "Each of you should use whatever gift you have received to serve others..." — 1 Peter 4:10
 * • Enables every member to find their place of service
 * • Empowers ministry leaders to schedule with confidence
 * • Tracks faithfulness and spiritual growth
 *
 * Safety Rules:
 * • Only assigned volunteers can confirm/decline
 * • Only confirmed assignments can be marked complete
 * • Cannot remove volunteer after confirmation without override
 *
 * This is the engine that turns members into ministers.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Volunteer.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class VolunteerRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE VOLUNTEER ROLE
         $method === 'POST' && $pathParts[0] === 'volunteer' && ($pathParts[1] ?? '') === 'role' && ($pathParts[2] ?? '') === 'create' => (function () {
            self::authenticate();
            self::authorize('settings.edit');

            $payload = self::getPayload();

            $result = Volunteer::createRole($payload);
            ResponseHelper::created($result, 'Volunteer role created');
         })(),

         // LIST ALL VOLUNTEER ROLES
         $method === 'GET' && $pathParts[0] === 'volunteer' && ($pathParts[1] ?? '') === 'role' && ($pathParts[2] ?? '') === 'all' => (function () {
            // Public access — everyone should see service opportunities
            self::authenticate(false);

            $result = Volunteer::getRoles();
            ResponseHelper::success(['data' => $result]);
         })(),

         // ASSIGN VOLUNTEERS TO EVENT (Bulk)
         $method === 'POST' && $pathParts[0] === 'volunteer' && ($pathParts[1] ?? '') === 'assign' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('events.edit');

            $eventId = self::getIdFromPath($pathParts, 2, 'Event ID');

            $payload = self::getPayload([
               'volunteers' => 'required|array'
            ]);

            $result = Volunteer::assign($eventId, $payload['volunteers']);
            ResponseHelper::success($result, 'Volunteers assigned');
         })(),

         // CONFIRM OR DECLINE ASSIGNMENT (Self-Service)
         $method === 'POST' && $pathParts[0] === 'volunteer' && ($pathParts[1] ?? '') === 'confirm' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            // Only the assigned volunteer can respond

            $assignmentId = self::getIdFromPath($pathParts, 2, 'Assignment ID');

            $payload = self::getPayload([
               'action' => 'required|in:confirm,decline'
            ]);

            $result = Volunteer::confirmAssignment($assignmentId, $payload['action']);
            ResponseHelper::success($result, 'Assignment ' . $payload['action'] . 'ed');
         })(),

         // MARK ASSIGNMENT AS COMPLETED
         $method === 'POST' && $pathParts[0] === 'volunteer' && ($pathParts[1] ?? '') === 'complete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('events.edit');

            $assignmentId = self::getIdFromPath($pathParts, 2, 'Assignment ID');

            $result = Volunteer::completeAssignment($assignmentId);
            ResponseHelper::success($result, 'Assignment marked as completed');
         })(),

         // GET VOLUNTEERS FOR EVENT (Paginated)
         $method === 'GET' && $pathParts[0] === 'volunteer' && ($pathParts[1] ?? '') === 'event' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('events.view');

            $eventId = self::getIdFromPath($pathParts, 2, 'Event ID');

            [$page, $limit] = self::getPagination(50, 100);

            $result = Volunteer::getByEvent($eventId, $page, $limit);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // REMOVE VOLUNTEER FROM EVENT
         $method === 'DELETE' && $pathParts[0] === 'volunteer' && ($pathParts[1] ?? '') === 'remove' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('events.edit');

            $assignmentId = self::getIdFromPath($pathParts, 2, 'Assignment ID');

            $result = Volunteer::remove($assignmentId);
            ResponseHelper::success($result, 'Volunteer removed from event');
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Volunteer endpoint not found'),
      };
   }
}

// Dispatch
VolunteerRoutes::handle();
