<?php

/**
 * Fiscal Year Management
 *
 * Orchestrates fiscal workflows and delegates data persistence to InfrastructureRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\System;

use Exception;

class FiscalYear
{
   public static function create(array $data): array
   {
      $repo = new InfrastructureRepository();

      Helpers::validateInput($data, ['start_date' => 'required|date', 'end_date' => 'required|date', 'branch_id' => 'required|numeric']);

      $branch = $repo->findBranchById((int) $data['branch_id']);
      if (!$branch)
         ResponseHelper::error('Invalid branch', 400);

      $fiscalYearId = $repo->createFiscalYear([
         'StartDate' => $data['start_date'],
         'EndDate' => $data['end_date'],
         'FiscalYearName' => $data['name'] ?? "FY " . date('Y', strtotime($data['start_date'])),
         'BranchID' => (int) $data['branch_id'],
         'Status' => $data['status'] ?? 'Active'
      ]);

      return ['status' => 'success', 'fiscal_year_id' => $fiscalYearId];
   }

   public static function close(int $fiscalYearId): array
   {
      $repo = new InfrastructureRepository();
      $repo->updateFiscalYear($fiscalYearId, ['Status' => 'Closed']);
      return ['status' => 'success'];
   }

   public static function delete(int $fiscalYearId): array
   {
      $repo = new InfrastructureRepository();
      if ($repo->isFiscalYearUsed($fiscalYearId)) {
         ResponseHelper::error('Cannot delete fiscal year with associated records', 400);
      }
      $repo->deleteFiscalYear($fiscalYearId);
      return ['status' => 'success'];
   }

   public static function get(int $fiscalYearId): array
   {
      $repo = new InfrastructureRepository();
      $fy = $repo->findFiscalYearById($fiscalYearId);
      if (!$fy)
         ResponseHelper::error('Fiscal year not found', 404);
      return $fy;
   }

   public static function getAll(array $filters, int $page = 1, int $limit = 50): array
   {
      $repo = new InfrastructureRepository();
      return $repo->getAllFiscalYears($filters, $page, $limit);
   }

   public static function getCurrent(): ?int
   {
      $repo = new InfrastructureRepository();
      return $repo->getCurrentFiscalYear();
   }
}