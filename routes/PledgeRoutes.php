<?php

/**
 * Pledge API Routes – v1
 *
 * Complete pledge management:
 * - Create/update pledge
 * - View single pledge with payment history
 * - List pledges with filters
 * - Record payment
 * - Track fulfillment progress
 * - Pledge types CRUD
 * - Statistics
 *
 * @package  AliveChMS\Routes
 * @version  1.1.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Pledge.php';
require_once __DIR__ . '/../core/PledgeType.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class PledgeRoutes extends BaseRoute
{
   public static function handle(): void
   {
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE PLEDGE
         $method === 'POST' && $path === 'pledge/create' => (function () {
            self::authenticate();
            self::authorize('manage_pledges');
            $payload = self::getPayload();
            $result = Pledge::create($payload);
            ResponseHelper::created($result, 'Pledge created');
         })(),

         // UPDATE PLEDGE
         $method === 'PUT' && $pathParts[0] === 'pledge' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_pledges');
            $pledgeId = self::getIdFromPath($pathParts, 2, 'Pledge ID');
            $payload = self::getPayload();
            $result = Pledge::update($pledgeId, $payload);
            ResponseHelper::success($result, 'Pledge updated');
         })(),

         // VIEW SINGLE PLEDGE
         $method === 'GET' && $pathParts[0] === 'pledge' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_pledges');
            $pledgeId = self::getIdFromPath($pathParts, 2, 'Pledge ID');
            $pledge = Pledge::get($pledgeId);
            ResponseHelper::success($pledge);
         })(),

         // LIST ALL PLEDGES
         $method === 'GET' && $path === 'pledge/all' => (function () {
            self::authenticate();
            self::authorize('view_pledges');
            [$page, $limit] = self::getPagination(10, 100);
            $filters = self::getFilters(['member_id', 'status', 'fiscal_year_id', 'pledge_type_id', 'start_date', 'end_date', 'search']);
            [$sortBy, $sortDir] = self::getSorting('PledgeDate', 'DESC', ['PledgeDate', 'PledgeAmount', 'MemberName', 'PledgeStatus', 'DueDate']);
            $filters['sort_by'] = $sortBy;
            $filters['sort_dir'] = $sortDir;
            $result = Pledge::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // GET PLEDGE STATISTICS
         $method === 'GET' && $path === 'pledge/stats' => (function () {
            self::authenticate();
            self::authorize('view_pledges');
            $fiscalYearId = !empty($_GET['fiscal_year_id']) ? (int)$_GET['fiscal_year_id'] : null;
            $result = Pledge::getStats($fiscalYearId);
            ResponseHelper::success($result);
         })(),

         // RECORD PAYMENT
         $method === 'POST' && $pathParts[0] === 'pledge' && ($pathParts[1] ?? '') === 'payment' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('record_pledge_payments');
            $pledgeId = self::getIdFromPath($pathParts, 2, 'Pledge ID');
            $payload = self::getPayload();
            $result = Pledge::recordPayment($pledgeId, $payload);
            ResponseHelper::success($result, 'Payment recorded');
         })(),

         // === PLEDGE TYPES ===

         // LIST PLEDGE TYPES
         $method === 'GET' && $path === 'pledge/types' => (function () {
            self::authenticate();
            self::authorize('view_pledges');
            $result = PledgeType::getAll();
            ResponseHelper::success($result['data']);
         })(),

         // CREATE PLEDGE TYPE
         $method === 'POST' && $path === 'pledge/type/create' => (function () {
            self::authenticate();
            self::authorize('manage_pledge_types');
            $payload = self::getPayload();
            $result = PledgeType::create($payload);
            ResponseHelper::created($result, 'Pledge type created');
         })(),

         // UPDATE PLEDGE TYPE
         $method === 'PUT' && $pathParts[0] === 'pledge' && ($pathParts[1] ?? '') === 'type' && ($pathParts[2] ?? '') === 'update' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_pledge_types');
            $typeId = self::getIdFromPath($pathParts, 3, 'Pledge Type ID');
            $payload = self::getPayload();
            $result = PledgeType::update($typeId, $payload);
            ResponseHelper::success($result, 'Pledge type updated');
         })(),

         // DELETE PLEDGE TYPE
         $method === 'DELETE' && $pathParts[0] === 'pledge' && ($pathParts[1] ?? '') === 'type' && ($pathParts[2] ?? '') === 'delete' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_pledge_types');
            $typeId = self::getIdFromPath($pathParts, 3, 'Pledge Type ID');
            $result = PledgeType::delete($typeId);
            ResponseHelper::success($result, 'Pledge type deleted');
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Pledge endpoint not found'),
      };
   }
}

PledgeRoutes::handle();
