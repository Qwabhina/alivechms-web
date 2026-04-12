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
        $recentMembers = $repo->getRecentMembers($branchId, 5);
        $upcomingEvents = $repo->getUpcomingEventsCount($branchId);
        
        $fiscalYearId = $repo->getActiveFiscalYearId($branchId);
        $finance = ['income' => 0.0, 'expenses' => 0.0];
        if ($fiscalYearId) {
            $finance = $repo->getFinanceOverview($branchId, $fiscalYearId);
        }
        
        $totalIncome = (float)($finance['income'] ?? 0);
        $totalExpenses = (float)($finance['expenses'] ?? 0);

        return [
            'members' => [
                'total' => (int)($stats['total'] ?? 0),
                'new_this_month' => (int)($stats['new_this_month'] ?? 0)
            ],
            'finance' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_balance' => $totalIncome - $totalExpenses
            ],
            'events' => [
                'upcoming' => $upcomingEvents
            ],
            'recent_members' => $recentMembers,
            'generated_at' => date('c')
        ];
    }
}