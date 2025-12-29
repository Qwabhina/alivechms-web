<?php

/**
 * Contribution API Routes – v1
 *
 * Full financial contribution management:
 * - Create new contribution
 * - Update existing contribution
 * - Soft-delete & restore
 * - View single contribution
 * - Paginated listing with powerful filtering
 * - Totals reporting
 *
 * All operations strictly permission-controlled.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Contribution.php';

class ContributionRoutes extends BaseRoute
{
    public static function handle(): void
    {
        // Get route variables from global scope
        global $method, $path, $pathParts;

        self::rateLimit(maxAttempts: 60, windowSeconds: 60);

        match (true) {
            // CREATE CONTRIBUTION
            $method === 'POST' && $path === 'contribution/create' => (function () {
                self::authenticate();
                self::authorize('create_contribution');

                $payload = self::getPayload();

                $result = Contribution::create($payload);
                self::success($result, 'Contribution created', 201);
            })(),

            // UPDATE CONTRIBUTION
            $method === 'PUT' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('edit_contribution');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');
                $payload = self::getPayload();

                $result = Contribution::update($contributionId, $payload);
                self::success($result, 'Contribution updated');
            })(),

            // SOFT DELETE CONTRIBUTION
            $method === 'DELETE' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('delete_contribution');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');

                $result = Contribution::delete($contributionId);
                self::success($result, 'Contribution deleted');
            })(),

            // RESTORE SOFT-DELETED CONTRIBUTION
            $method === 'POST' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'restore' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('delete_contribution');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');

                $result = Contribution::restore($contributionId);
                self::success($result, 'Contribution restored');
            })(),

            // VIEW SINGLE CONTRIBUTION
            $method === 'GET' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('view_contribution');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');

                $result = Contribution::get($contributionId);
                self::success($result);
            })(),

            // LIST ALL CONTRIBUTIONS (Paginated + Filtered)
            $method === 'GET' && $path === 'contribution/all' => (function () {
                self::authenticate();
                self::authorize('view_contribution');

                [$page, $limit] = self::getPagination(10, 100);

                $filters = self::getFilters([
                    'contribution_type_id',
                    'member_id',
                    'fiscal_year_id',
                    'start_date',
                    'end_date'
                ]);

                $result = Contribution::getAll($page, $limit, $filters);
                self::paginated($result['data'], $result['pagination']['total'], $page, $limit);
            })(),

            // TOTAL CONTRIBUTIONS (Reporting)
            $method === 'GET' && $path === 'contribution/total' => (function () {
                self::authenticate();
                self::authorize('view_contribution');

                $filters = self::getFilters([
                    'contribution_type_id',
                    'member_id',
                    'fiscal_year_id',
                    'start_date',
                    'end_date'
                ]);

                $result = Contribution::getTotal($filters);
                self::success($result);
            })(),

            // FALLBACK
            default => self::error('Contribution endpoint not found', 404),
        };
    }
}

// Dispatch
ContributionRoutes::handle();