<?php

/**
 * Financial Reporting Service
 *
 * Orchestrates financial reports and delegates data fetching to ReportingRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\Financial\ReportingRepository;
use AliveChMS\Core\System\ResponseHelper;

class Finance
{
   public static function getIncomeStatement(int $fiscalYearId): array
   {
      $repo = new ReportingRepository();
      $data = $repo->getIncomeStatement($fiscalYearId);

      $totalIncome = array_sum(array_column($data['income'], 'total'));
      $totalExpense = array_sum(array_column($data['expenses'], 'total'));

      return [
         'income' => $data['income'],
         'expenses' => $data['expenses'],
         'totals' => [
            'income' => $totalIncome,
            'expense' => $totalExpense,
            'net' => $totalIncome - $totalExpense
         ]
      ];
   }
}