<?php

/**
 * Pledge Management Service
 *
 * Orchestrates pledge lifecycles and delegates data operations to PledgeRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.1.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\Financial\PledgeRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use Exception;

class Pledge
{
   private const STATUS_ACTIVE     = 'Active';
   private const STATUS_FULFILLED  = 'Fulfilled';
   private const STATUS_CANCELLED  = 'Cancelled';

   /**
    * Get pledge statistics
    */
   public static function getStats(?int $fiscalYearId = null): array
   {
      $repo = new PledgeRepository();
      $stats = $repo->getStats($fiscalYearId);

      return [
         'total_amount' => (float) $stats['total']['total'],
         'total_count' => (int) $stats['total']['count'],
         'active_amount' => (float) $stats['active']['total'],
         'fulfilled_amount' => (float) $stats['fulfilled']['total'],
         'payments_total' => (float) $stats['payments']['total'],
         'outstanding_amount' => (float) $stats['outstanding']
      ];
   }

   /**
    * Create a new pledge
    */
   public static function create(array $data): array
   {
      $repo = new PledgeRepository();

      Helpers::validateInput($data, [
         'member_id'       => 'required|numeric',
         'pledge_type_id' => 'required|numeric',
         'amount'          => 'required|numeric',
         'pledge_date' => 'required|date'
      ]);

      if (!$repo->isValidPledgeType((int) $data['pledge_type_id'])) {
         ResponseHelper::error('Invalid pledge type', 400);
      }

      $pledgeId = $repo->create([
         'MbrID' => (int) $data['member_id'],
         'PledgeTypeID' => (int) $data['pledge_type_id'],
         'FiscalYearID' => !empty($data['fiscal_year_id']) ? (int) $data['fiscal_year_id'] : null,
         'PledgeAmount' => (float) $data['amount'],
         'PledgeDate'     => $data['pledge_date'],
         'DueDate'        => $data['due_date'] ?? null,
         'PledgeStatus'   => self::STATUS_ACTIVE,
         'Description'    => $data['description'] ?? null,
         'CreatedBy'      => Auth::getCurrentUserId(),
         'CreatedAt'      => date('Y-m-d H:i:s')
      ]);

      Helpers::logError("New pledge created: ID $pledgeId");
      return ['status' => 'success', 'pledge_id' => $pledgeId];
   }

   /**
    * Record payment against a pledge
    */
   public static function recordPayment(int $pledgeId, array $data): array
   {
      $repo = new PledgeRepository();
      $pledge = $repo->findById($pledgeId);

      if (!$pledge || $pledge['PledgeStatus'] !== self::STATUS_ACTIVE) {
         ResponseHelper::error('Active pledge not found', 404);
      }

      Helpers::validateInput($data, ['amount' => 'required|numeric', 'payment_date' => 'required|date']);

      $repo->beginTransaction();
      try {
         $repo->createPayment([
            'PledgeID'       => $pledgeId,
            'ContributionID' => !empty($data['contribution_id']) ? (int)$data['contribution_id'] : null,
            'PaymentAmount' => (float) $data['amount'],
            'PaymentDate'    => $data['payment_date'],
            'RecordedBy'     => Auth::getCurrentUserId(),
            'RecordedAt'     => date('Y-m-d H:i:s')
         ]);

         $paid = $repo->getTotalPaid($pledgeId);
         if ($paid >= (float)$pledge['PledgeAmount']) {
            $repo->update($pledgeId, ['PledgeStatus' => self::STATUS_FULFILLED]);
         }

         $repo->commit();
         return ['status' => 'success', 'message' => 'Payment recorded'];
      } catch (Exception $e) {
         $repo->rollBack();
         throw $e;
      }
   }

   public static function get(int $pledgeId): array
   {
      $repo = new PledgeRepository();
      $pledge = $repo->findById($pledgeId);
      if (!$pledge)
         ResponseHelper::error('Pledge not found', 404);

      $pledge['payments'] = $repo->getPayments($pledgeId);
      $pledge['total_paid'] = $repo->getTotalPaid($pledgeId);
      $pledge['balance'] = (float) $pledge['PledgeAmount'] - $pledge['total_paid'];

      return $pledge;
   }

   public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
   {
      $repo = new PledgeRepository();
      $offset = ($page - 1) * $limit;
      $result = $repo->findAll($limit, $offset, $filters);

      return [
         'data' => $result['data'],
         'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
            'pages' => (int) ceil($result['total'] / $limit)
         ]
      ];
   }
}