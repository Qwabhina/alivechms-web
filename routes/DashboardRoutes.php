<?php

/**
 * Dashboard API Routes – v1
 *
 * Single, powerful endpoint providing a comprehensive real-time overview
 * for church leadership:
 * - Membership statistics
 * - Financial summary
 * - Recent attendance trends
 * - Upcoming events
 * - Pending approvals
 * - Recent activity feed
 *
 * Fully branch-aware and permission-controlled.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Dashboard.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class DashboardRoutes extends BaseRoute
{
    public static function handle(): void
    {
        // Get route variables from global scope
        global $method, $path, $pathParts;

        self::rateLimit(maxAttempts: 60, windowSeconds: 60);

        match (true) {
            // DASHBOARD OVERVIEW
            $method === 'GET' && $path === 'dashboard/overview' => (function () {
                self::authenticate();
                self::authorize('reports.view');

                try {
                    $overview = Dashboard::getOverview();
                    ResponseHelper::success($overview);
                } catch (Exception $e) {
                    Helpers::logError("Dashboard generation failed: " . $e->getMessage());
                    ResponseHelper::serverError('Failed to generate dashboard');
                }
            })(),

            // FALLBACK
            default => ResponseHelper::notFound('Dashboard endpoint not found'),
        };
    }
}

// Dispatch
DashboardRoutes::handle();