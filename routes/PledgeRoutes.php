<?php

/**
 * Pledge API Routes – v1
 *
 * Complete pledge management:
 * - Create pledge
 * - View single pledge with payment history
 * - List pledges with filters
 * - Record payment
 * - Track fulfillment progress (percentage, balance, status)
 *
 * All operations fully permission-controlled.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Pledge.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class PledgeRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
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

         // VIEW SINGLE PLEDGE (with payments & progress)
         $method === 'GET' && $pathParts[0] === 'pledge' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_pledges');

            $pledgeId = self::getIdFromPath($pathParts, 2, 'Pledge ID');

            $pledge = Pledge::get($pledgeId);
            ResponseHelper::success($pledge);
         })(),

         // LIST ALL PLEDGES (Paginated + Filtered)
         $method === 'GET' && $path === 'pledge/all' => (function () {
            self::authenticate();
            self::authorize('view_pledges');

            [$page, $limit] = self::getPagination(10, 100);

            $filters = self::getFilters(['member_id', 'status', 'fiscal_year_id', 'search']);

            // Get sorting parameters with allowed columns
            [$sortBy, $sortDir] = self::getSorting(
               'PledgeDate',
               'DESC',
               ['PledgeDate', 'PledgeAmount', 'MemberName', 'PledgeStatus']
            );
            $filters['sort_by'] = $sortBy;
            $filters['sort_dir'] = $sortDir;

            $result = Pledge::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // RECORD PAYMENT AGAINST PLEDGE
         $method === 'POST' && $pathParts[0] === 'pledge' && ($pathParts[1] ?? '') === 'payment' && ($pathParts[2] ?? '') === 'add' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('record_pledge_payments');

            $pledgeId = self::getIdFromPath($pathParts, 3, 'Pledge ID');

            $payload = self::getPayload();

            $result = Pledge::recordPayment($pledgeId, $payload);
            ResponseHelper::success($result, 'Payment recorded');
         })(),

         // GET PLEDGE FULFILLMENT PROGRESS (Percentage, Balance, Status)
         $method === 'GET' && $pathParts[0] === 'pledge' && ($pathParts[1] ?? '') === 'progress' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_pledges');

            $pledgeId = self::getIdFromPath($pathParts, 2, 'Pledge ID');

            $pledge = Pledge::get($pledgeId);

            $pledgeAmount = (float)$pledge['PledgeAmount'];
            $totalPaid    = (float)$pledge['total_paid'];
            $balance      = (float)$pledge['balance'];

            $progress = $pledgeAmount > 0 ? round(($totalPaid / $pledgeAmount) * 100, 2) : 0;

            $status = match (true) {
               $progress >= 100 => 'Fulfilled',
               $progress > 0    => 'In Progress',
               default          => 'Not Started'
            };

            $result = [
               'pledge_id'        => (int)$pledge['PledgeID'],
               'pledge_amount'    => number_format($pledgeAmount, 2),
               'total_paid'       => number_format($totalPaid, 2),
               'balance'          => number_format($balance, 2),
               'progress_percent' => $progress,
               'status'           => $status,
               'payments_count'   => count($pledge['payments']),
               'last_payment_date' => !empty($pledge['payments']) ? $pledge['payments'][0]['PaymentDate'] : null
            ];

            ResponseHelper::success($result);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Pledge endpoint not found'),
      };
   }
}

// Dispatch
PledgeRoutes::handle();
