<?php

/**
 * Dashboard Analytics Service
 *
 * Orchestrates dashboard data assembly using ReportingRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\Operations\ReportingRepository;
use AliveChMS\Core\Identity\Auth;

class Dashboard
{
   public static function getOverview(): array
   {
      $repo = new ReportingRepository();
      $branchId = Auth::getUserBranchId();

      $stats = $repo->getDashboardStats($branchId);
      $activity = $repo->getRecentActivity($branchId);

      return [
         'membership' => $stats,
         'recent_activity' => $activity,
         'generated_at' => date('c')
      ];
   }
}