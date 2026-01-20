<?php

/**
 * Contribution API Routes – v2.0
 *
 * Full financial contribution management:
 * - Create new contribution (FiscalYearID optional)
 * - Update existing contribution
 * - Soft-delete & restore with audit trail
 * - View single contribution
 * - Paginated listing with powerful filtering
 * - Totals reporting
 * - Contribution statistics
 * - Receipt generation
 * - Member statements
 *
 * Refactored for optimized schema v2.0:
 * - Uses payment_method (was paymentoption)
 * - FiscalYearID is optional
 * - Enhanced audit trail
 *
 * All operations strictly permission-controlled.
 *
 * @package  AliveChMS\Routes
 * @version  2.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Contribution.php';
require_once __DIR__ . '/../core/ContributionType.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

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
                self::authorize('contributions.create');

                $payload = self::getPayload();

                $result = Contribution::create($payload);
                ResponseHelper::created($result, 'Contribution created');
            })(),

            // UPDATE CONTRIBUTION
            $method === 'PUT' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('contributions.edit');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');
                $payload = self::getPayload();

                $result = Contribution::update($contributionId, $payload);
                ResponseHelper::success($result, 'Contribution updated');
            })(),

            // SOFT DELETE CONTRIBUTION
            $method === 'DELETE' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('contributions.delete');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');

                $result = Contribution::delete($contributionId);
                ResponseHelper::success($result, 'Contribution deleted');
            })(),

            // RESTORE SOFT-DELETED CONTRIBUTION
            $method === 'POST' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'restore' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('contributions.delete');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');

                $result = Contribution::restore($contributionId);
                ResponseHelper::success($result, 'Contribution restored');
            })(),

            // VIEW SINGLE CONTRIBUTION
            $method === 'GET' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('finances.view');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');

                $result = Contribution::get($contributionId);
                ResponseHelper::success($result);
            })(),

            // LIST ALL CONTRIBUTIONS (Paginated + Filtered)
            $method === 'GET' && $path === 'contribution/all' => (function () {
                self::authenticate();
                self::authorize('finances.view');

                [$page, $limit] = self::getPagination(10, 100);

                $filters = self::getFilters([
                    'contribution_type_id',
                    'member_id',
                    'fiscal_year_id',
                    'start_date',
                    'end_date',
                    'search'
                ]);

                // Get sorting parameters with allowed columns
                [$sortBy, $sortDir] = self::getSorting(
                    'ContributionDate',
                    'DESC',
                    ['ContributionDate', 'ContributionAmount', 'MemberName', 'ContributionTypeName']
                );
                $filters['sort_by'] = $sortBy;
                $filters['sort_dir'] = $sortDir;

                $result = Contribution::getAll($page, $limit, $filters);
                ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
            })(),

            // TOTAL CONTRIBUTIONS (Reporting)
            $method === 'GET' && $path === 'contribution/total' => (function () {
                self::authenticate();
                self::authorize('finances.view');

                $filters = self::getFilters([
                    'contribution_type_id',
                    'member_id',
                    'fiscal_year_id',
                    'start_date',
                    'end_date'
                ]);

                $result = Contribution::getTotal($filters);
                ResponseHelper::success($result);
            })(),

            // GET CONTRIBUTION STATISTICS
            $method === 'GET' && $path === 'contribution/stats' => (function () {
                self::authenticate();
                self::authorize('finances.view');

                $fiscalYearId = !empty($_GET['fiscal_year_id']) ? (int)$_GET['fiscal_year_id'] : null;
                $result = Contribution::getStats($fiscalYearId);
                ResponseHelper::success($result);
            })(),

            // GET CONTRIBUTION TYPES
            $method === 'GET' && $path === 'contribution/types' => (function () {
                self::authenticate();
                self::authorize('finances.view');

                $result = Contribution::getTypes();
                ResponseHelper::success(['data' => $result]);
            })(),

            // CREATE CONTRIBUTION TYPE
            $method === 'POST' && $path === 'contribution/type/create' => (function () {
                self::authenticate();
                self::authorize('settings.edit');

                $payload = self::getPayload();
                $result = ContributionType::create($payload);
                ResponseHelper::created($result, 'Contribution type created');
            })(),

            // UPDATE CONTRIBUTION TYPE
            $method === 'PUT' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'type' && ($pathParts[2] ?? '') === 'update' && isset($pathParts[3]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('settings.edit');

                $typeId = self::getIdFromPath($pathParts, 3, 'Contribution Type ID');
                $payload = self::getPayload();
                $result = ContributionType::update($typeId, $payload);
                ResponseHelper::success($result, 'Contribution type updated');
            })(),

            // DELETE CONTRIBUTION TYPE
            $method === 'DELETE' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'type' && ($pathParts[2] ?? '') === 'delete' && isset($pathParts[3]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('settings.edit');

                $typeId = self::getIdFromPath($pathParts, 3, 'Contribution Type ID');
                $result = ContributionType::delete($typeId);
                ResponseHelper::success($result, 'Contribution type deleted');
            })(),

            // GET PAYMENT METHODS
            $method === 'GET' && $path === 'contribution/payment-methods' => (function () {
                self::authenticate();
                self::authorize('finances.view');

                $result = Contribution::getPaymentMethods();
                ResponseHelper::success(['data' => $result]);
            })(),

            // GET CONTRIBUTION RECEIPT
            $method === 'GET' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'receipt' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('finances.view');

                $contributionId = self::getIdFromPath($pathParts, 2, 'Contribution ID');

                $result = Contribution::getReceipt($contributionId);
                ResponseHelper::success($result);
            })(),

            // GET MEMBER CONTRIBUTION STATEMENT
            $method === 'GET' && $pathParts[0] === 'contribution' && ($pathParts[1] ?? '') === 'statement' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('finances.view');

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');
                $fiscalYearId = !empty($_GET['fiscal_year_id']) ? (int)$_GET['fiscal_year_id'] : null;

                $result = Contribution::getMemberStatement($memberId, $fiscalYearId);
                ResponseHelper::success($result);
            })(),

            // FALLBACK
            default => ResponseHelper::notFound('Contribution endpoint not found'),
        };
    }
}

// Dispatch
ContributionRoutes::handle();