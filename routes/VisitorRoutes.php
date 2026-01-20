<?php

/**
 * Visitor API Routes
 *
 * Complete visitor management and follow-up system
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Visitor.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class VisitorRoutes extends BaseRoute
{
   public static function handle(): void
   {
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE VISITOR
         $method === 'POST' && $path === 'visitor/create' => (function () {
            self::authenticate();
            self::authorize('visitors.create');

            $payload = self::getPayload();
            $result = Visitor::create($payload);
            ResponseHelper::created($result, 'Visitor recorded');
         })(),

         // UPDATE VISITOR
         $method === 'PUT' && $pathParts[0] === 'visitor' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('visitors.edit');

            $visitorId = self::getIdFromPath($pathParts, 2, 'Visitor ID');
            $payload = self::getPayload();
            $result = Visitor::update($visitorId, $payload);
            ResponseHelper::success($result, 'Visitor updated');
         })(),

         // DELETE VISITOR
         $method === 'DELETE' && $pathParts[0] === 'visitor' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('visitors.delete');

            $visitorId = self::getIdFromPath($pathParts, 2, 'Visitor ID');
            $result = Visitor::delete($visitorId);
            ResponseHelper::success($result, 'Visitor deleted');
         })(),

         // VIEW SINGLE VISITOR
         $method === 'GET' && $pathParts[0] === 'visitor' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('visitors.view');

            $visitorId = self::getIdFromPath($pathParts, 2, 'Visitor ID');
            $visitor = Visitor::get($visitorId);
            ResponseHelper::success($visitor);
         })(),

         // LIST ALL VISITORS
         $method === 'GET' && $path === 'visitor/all' => (function () {
            self::authenticate();
            self::authorize('visitors.view');

            [$page, $limit] = self::getPagination(25, 100);

            $filters = [];
            if (!empty($_GET['branch_id'])) {
               $filters['branch_id'] = (int)$_GET['branch_id'];
            }
            if (!empty($_GET['source'])) {
               $filters['source'] = $_GET['source'];
            }
            if (!empty($_GET['assigned_to'])) {
               $filters['assigned_to'] = (int)$_GET['assigned_to'];
            }
            if (isset($_GET['converted'])) {
               $filters['converted'] = filter_var($_GET['converted'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($_GET['interested_in_membership'])) {
               $filters['interested_in_membership'] = filter_var($_GET['interested_in_membership'], FILTER_VALIDATE_BOOLEAN);
            }
            if (!empty($_GET['start_date'])) {
               $filters['start_date'] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
               $filters['end_date'] = $_GET['end_date'];
            }
            if (!empty($_GET['search'])) {
               $filters['search'] = $_GET['search'];
            }

            $result = Visitor::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // ASSIGN FOLLOW-UP
         $method === 'POST' && $pathParts[0] === 'visitor' && ($pathParts[1] ?? '') === 'assign-followup' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('visitors.manage');

            $visitorId = self::getIdFromPath($pathParts, 2, 'Visitor ID');
            $payload = self::getPayload(['member_id' => 'required|numeric']);

            $result = Visitor::assignFollowUp($visitorId, (int)$payload['member_id']);
            ResponseHelper::success($result, 'Follow-up assigned');
         })(),

         // RECORD RETURN VISIT
         $method === 'POST' && $pathParts[0] === 'visitor' && ($pathParts[1] ?? '') === 'return-visit' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('visitors.manage');

            $visitorId = self::getIdFromPath($pathParts, 2, 'Visitor ID');
            $payload = self::getPayload(['visit_date' => 'required|date']);

            $result = Visitor::recordReturnVisit($visitorId, $payload['visit_date']);
            ResponseHelper::success($result, 'Return visit recorded');
         })(),

         // GET VISITOR STATISTICS
         $method === 'GET' && $path === 'visitor/stats' => (function () {
            self::authenticate();
            self::authorize('visitors.view');

            $filters = [];
            if (!empty($_GET['branch_id'])) {
               $filters['branch_id'] = (int)$_GET['branch_id'];
            }
            if (!empty($_GET['start_date'])) {
               $filters['start_date'] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
               $filters['end_date'] = $_GET['end_date'];
            }

            $result = Visitor::getStats($filters);
            ResponseHelper::success($result);
         })(),

         // CONVERT VISITOR TO MEMBER
         $method === 'POST' && $pathParts[0] === 'visitor' && ($pathParts[1] ?? '') === 'convert' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('visitors.manage');

            $visitorId = self::getIdFromPath($pathParts, 2, 'Visitor ID');
            $payload = self::getPayload();

            $result = Visitor::convertToMember($visitorId, $payload);
            ResponseHelper::success($result, 'Visitor converted to member');
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Visitor endpoint not found'),
      };
   }
}

VisitorRoutes::handle();
