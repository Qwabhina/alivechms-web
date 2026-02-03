<?php

/**
 * Contribution Management Service
 *
 * Handles business logic, validation, and orchestration for contributions.
 * Delegates database operations to ContributionRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.1.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\Financial\ContributionRepository;
use AliveChMS\Core\Services\MoneyValidator;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use AliveChMS\Core\Stats\ContributionStats;
use Exception;

class Contribution
{
   /**
    * Create a new contribution record
    *
    * @param array $data Contribution payload
    * @return array ['status' => 'success', 'contribution_id' => int]
    * @throws Exception On validation or database failure
    */
   public static function create(array $data): array
   {
      $repo = new ContributionRepository();

      Helpers::validateInput($data, [
         'amount'               => 'required|numeric',
         'date'                 => 'required|date',
         'contribution_type_id' => 'required|numeric',
         'member_id'            => 'required|numeric',
         'payment_method_id'    => 'required|numeric',
         'fiscal_year_id'       => 'numeric|nullable',
         'description'          => 'max:500|nullable',
         'branch_id' => 'numeric|nullable'
      ]);

      // Validate and format amount
      $amount = MoneyValidator::validateAmount($data['amount'], 'Contribution amount');
      $contributionDate = $data['date'];
      $memberId         = (int)$data['member_id'];
      $typeId           = (int)$data['contribution_type_id'];
      $paymentMethodId  = (int)$data['payment_method_id'];
      $fiscalYearId     = !empty($data['fiscal_year_id']) ? (int)$data['fiscal_year_id'] : null;
      $branchId = !empty($data['branch_id']) ? (int) $data['branch_id'] : 1; // Default to main branch

      if ($amount <= 0) {
         ResponseHelper::error('Contribution amount must be greater than zero', 400);
      }

      if ($contributionDate > date('Y-m-d')) {
         ResponseHelper::error('Contribution date cannot be in the future', 400);
      }

      // Foreign Key Validation via Repository
      $valid = $repo->validateForeignKeys($memberId, $typeId, $paymentMethodId, $fiscalYearId);

      if ($valid['member_ok'] == 0)   ResponseHelper::error('Invalid or inactive member', 400);
      if ($valid['type_ok'] == 0)     ResponseHelper::error('Invalid contribution type', 400);
      if ($valid['payment_ok'] == 0)  ResponseHelper::error('Invalid payment method', 400);
      if ($fiscalYearId !== null && isset($valid['fiscal_ok']) && $valid['fiscal_ok'] == 0) {
         ResponseHelper::error('Invalid or inactive fiscal year', 400);
      }

      $repo->beginTransaction();
      try {
         $contributionId = $repo->create([
            'ContributionAmount'       => $amount,
            'ContributionDate'         => $contributionDate,
            'ContributionTypeID'       => $typeId,
            'PaymentMethodID'          => $paymentMethodId,
            'MbrID'                    => $memberId,
            'FiscalYearID'             => $fiscalYearId,
            'BranchID' => $branchId,
            'Notes' => $data['description'] ?? null,
            'Deleted'                  => 0,
            'RecordedBy'               => Auth::getCurrentUserId(),
            'RecordedAt'               => date('Y-m-d H:i:s')
         ]);

         $repo->commit();

         Helpers::logError("New contribution recorded: ID $contributionId | Amount $amount | Member $memberId");
         return ['status' => 'success', 'contribution_id' => $contributionId];
      } catch (Exception $e) {
         $repo->rollBack();
         Helpers::logError("Contribution creation failed: " . $e->getMessage());
         throw $e;
      }
   }

   /**
    * Update an existing contribution
    */
   public static function update(int $contributionId, array $data): array
   {
      $repo = new ContributionRepository();

      $existing = $repo->findById($contributionId);
      if (!$existing || $existing['Deleted'] == 1) {
         ResponseHelper::error('Contribution not found or deleted', 404);
      }

      Helpers::validateInput($data, [
         'amount'               => 'numeric|nullable',
         'date'                 => 'date|nullable',
         'contribution_type_id' => 'numeric|nullable',
         'payment_method_id'    => 'numeric|nullable',
         'fiscal_year_id'       => 'numeric|nullable',
         'description'          => 'max:500|nullable',
      ]);

      $updateData = [];

      if (isset($data['amount'])) {
         $updateData['ContributionAmount'] = MoneyValidator::validateAmount($data['amount'], 'Contribution amount');
      }
      if (!empty($data['date'])) {
         if ($data['date'] > date('Y-m-d')) {
            ResponseHelper::error('Date cannot be in the future', 400);
         }
         $updateData['ContributionDate'] = $data['date'];
      }
      if (!empty($data['contribution_type_id'])) {
         $updateData['ContributionTypeID'] = (int) $data['contribution_type_id'];
      }
      if (!empty($data['payment_method_id'])) {
         $updateData['PaymentMethodID'] = (int) $data['payment_method_id'];
      }
      if (isset($data['fiscal_year_id'])) {
         $updateData['FiscalYearID'] = !empty($data['fiscal_year_id']) ? (int) $data['fiscal_year_id'] : null;
      }
      if (isset($data['description'])) {
         $updateData['Notes'] = $data['description'];
      }

      if (!empty($updateData)) {
         // TODO: Add UpdatedBy column to schema if it doesn't exist, strictly speaking user should add it.
         // Assuming table has it or ignoring for now to adhere to existing schema.
         // Schema check: contribution table doesn't have UpdatedBy in provided sql snippet.
         // We will just update what we can.
         // Actually, let's keep it simple.
         $repo->update($contributionId, $updateData);
      }

      return ['status' => 'success', 'contribution_id' => $contributionId];
   }

   /**
    * Soft delete a contribution
    */
   public static function delete(int $contributionId): array
   {
      $repo = new ContributionRepository();

      $existing = $repo->findById($contributionId);
      if (!$existing || $existing['Deleted'] == 1) {
         ResponseHelper::error('Contribution not found or already deleted', 404);
      }

      $repo->update($contributionId, [
         'Deleted' => 1,
         'DeletedBy' => Auth::getCurrentUserId(),
         'DeletedAt' => date('Y-m-d H:i:s')
      ]);

      Helpers::logError("Contribution soft-deleted: ID $contributionId by " . Auth::getCurrentUserId());
      return ['status' => 'success'];
   }

   /**
    * Restore a soft-deleted contribution
    */
   public static function restore(int $contributionId): array
   {
      $repo = new ContributionRepository();

      // We need to bypass the findById 'Deleted=0' check to find deleted ones.
      // But repo->findById filters them.
      // Ideally repository should have findWithTrash or generic find.
      // For now, simple update.
      $repo->update($contributionId, ['Deleted' => 0]);

      return ['status' => 'success'];
   }

   /**
    * Retrieve a single contribution
    */
   public static function get(int $contributionId): array
   {
      $repo = new ContributionRepository();
      $contribution = $repo->findById($contributionId);

      if (!$contribution) {
         ResponseHelper::error('Contribution not found', 404);
      }

      return $contribution;
   }

   /**
    * Retrieve paginated contributions
    */
   public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
   {
      $repo = new ContributionRepository();
      $offset = ($page - 1) * $limit;

      // Handle Sorting
      $orderBy = ['c.ContributionDate' => 'DESC'];
      if (!empty($filters['sort_by'])) {
         $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');
         $columnMap = [
            'ContributionDate' => 'c.ContributionDate',
            'ContributionAmount' => 'c.ContributionAmount',
            'MemberName' => 'm.MbrFirstName',
            'ContributionTypeName' => 'ct.ContributionTypeName',
            'date' => 'c.ContributionDate',
            'amount' => 'c.ContributionAmount',
            'member' => 'm.MbrFirstName',
            'type' => 'ct.ContributionTypeName'
         ];

         if (isset($columnMap[$filters['sort_by']])) {
            $orderBy = [$columnMap[$filters['sort_by']] => $sortDir];
         }
      }

      $result = $repo->findAll($limit, $offset, $filters, $orderBy);

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

   /**
    * Get total contributions (Proxy to Stats)
    */
   public static function getTotal(array $filters = []): array
   {
      $stats = new ContributionStats();
      return $stats->getTotal($filters);
   }

   /**
    * Get contribution statistics (Proxy to Stats)
    */
   public static function getStats(?int $fiscalYearId = null): array
   {
      $stats = new ContributionStats();
      return $stats->getStats($fiscalYearId);
   }

   /**
    * Generate receipt data
    */
   public static function getReceipt(int $contributionId): array
   {
      $repo = new ContributionRepository();
      $contribution = $repo->findById($contributionId);

      if (!$contribution) {
         ResponseHelper::error('Contribution not found', 404);
      }

      // Generate receipt number (format: RCP-YYYY-XXXXX)
      $receiptNumber = sprintf('RCP-%s-%05d', date('Y'), $contributionId);

      return [
         'receipt_number' => $receiptNumber,
         'contribution_id' => $contribution['ContributionID'],
         'date' => $contribution['ContributionDate'],
         'amount' => (float)$contribution['ContributionAmount'],
         'type' => $contribution['ContributionTypeName'],
         'payment_method' => $contribution['PaymentMethodName'],
         'fiscal_year' => $contribution['FiscalYearName'] ?? 'N/A',
         'description' => $contribution['Notes'],
         'recorded_at' => null, // Not in selectWithJoin fields by default, can add if needed
         'member' => [
            'id' => $contribution['MbrID'],
            'name' => $contribution['MbrFirstName'] . ' ' . $contribution['MbrFamilyName'],
            'email' => $contribution['MbrEmailAddress']
         ],
         'church' => [
            'name' => $contribution['BranchName'] ?? 'Church Name',
            'address' => $contribution['BranchAddress'] ?? '',
            'phone' => $contribution['BranchPhoneNumber'] ?? '',
            'email' => $contribution['BranchEmailAddress'] ?? ''
         ],
         'generated_at' => date('Y-m-d H:i:s')
      ];
   }

   /**
    * Get member statement
    */
   public static function getMemberStatement(int $memberId, ?int $fiscalYearId = null): array
   {
      $repo = new ContributionRepository();
      $orm = new \AliveChMS\Core\ORM(); // Using ORM directly for member lookup or should use MemberRepo?
      // Ideally MemberRepo. But to keep scope limited, we can use simple query here or logic in Repo.
      // Let's use MemberRepository for member details!
      $memberRepo = new \AliveChMS\Core\Repositories\MemberRepository();
      $member = $memberRepo->findById($memberId);

      if (!$member) {
         ResponseHelper::error('Member not found', 404);
      }

      // Get contributions
      $contributions = $repo->findByMember($memberId, $fiscalYearId);
      $totalsByType = $repo->getTotalsByType($memberId, $fiscalYearId);

      $grandTotal = array_reduce($contributions, function ($sum, $c) {
         return $sum + (float)$c['ContributionAmount'];
      }, 0);

      // We don't have Fiscal Year object explicitly fetched here, but we can return without it or fetch it if crucial.
      // Keeping it simple.

      return [
         'statement_number' => sprintf('STM-%s-%05d', date('Y'), $memberId),
         'generated_at' => date('Y-m-d H:i:s'),
         'member' => [
            'id' => $member['MbrID'],
            'name' => $member['MbrFirstName'] . ' ' . $member['MbrFamilyName'],
            'email' => $member['MbrEmailAddress']
         ],
         'contributions' => $contributions,
         'totals_by_type' => $totalsByType,
         'grand_total' => $grandTotal
      ];
   }

   /**
    * Get active contribution types
    */
   public static function getTypes(): array
   {
      $repo = new ContributionRepository();
      return $repo->getTypes();
   }

   /**
    * Get active payment methods
    */
   public static function getPaymentMethods(): array
   {
      $repo = new ContributionRepository();
      return $repo->getPaymentMethods();
   }
}