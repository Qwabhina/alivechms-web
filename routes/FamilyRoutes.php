<?php

/**
 * Family API Routes – v1
 *
 * Complete family unit management with member lifecycle:
 * - Create family with head of household
 * - Update family details
 * - Soft-delete family
 * - View single family with all members
 * - Paginated listing with filtering
 * - Add/remove/update member roles
 *
 * All operations fully permission-controlled and auditable.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Family.php';

class FamilyRoutes extends BaseRoute
{
    public static function handle(): void
    {
        // Get route variables from global scope
        global $method, $path, $pathParts;

        // Rate limit for family operations
        self::rateLimit(maxAttempts: 50, windowSeconds: 60);

        match (true) {
            // CREATE FAMILY
            $method === 'POST' && $path === 'family/create' => (function () {
                self::authenticate();
                self::authorize('manage_families');

                $payload = self::getPayload([
                    'family_name' => 'required|max:100',
                    'head_id'     => 'required|numeric',
                    'branch_id'   => 'required|numeric',
                    'address'     => 'max:255|nullable',
                    'phone'       => 'max:20|nullable',
                    'email'       => 'email|nullable'
                ]);

                $result = self::withTransaction(function () use ($payload) {
                    return Family::create($payload);
                });
                self::success($result, 'Family created', 201);
            })(),

            // UPDATE FAMILY
            $method === 'PUT' && $pathParts[0] === 'family' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('manage_families');

                $familyId = self::getIdFromPath($pathParts, 2, 'Family ID');

                $payload = self::getPayload([
                    'family_name' => 'max:100|nullable',
                    'head_id'     => 'numeric|nullable',
                    'branch_id'   => 'numeric|nullable',
                    'address'     => 'max:255|nullable',
                    'phone'       => 'max:20|nullable',
                    'email'       => 'email|nullable'
                ]);

                $result = self::withTransaction(function () use ($familyId, $payload) {
                    return Family::update($familyId, $payload);
                });
                self::success($result, 'Family updated');
            })(),

            // SOFT DELETE FAMILY
            $method === 'DELETE' && $pathParts[0] === 'family' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('manage_families');

                $familyId = self::getIdFromPath($pathParts, 2, 'Family ID');

                $result = self::withTransaction(function () use ($familyId) {
                    return Family::softDelete($familyId);
                });
                self::success($result, 'Family deleted');
            })(),

            // VIEW SINGLE FAMILY WITH MEMBERS
            $method === 'GET' && $pathParts[0] === 'family' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('view_families');

                $familyId = self::getIdFromPath($pathParts, 2, 'Family ID');

                $family = Family::get($familyId);
                self::success($family);
            })(),

            // LIST ALL FAMILIES (PAGINATED) - For dropdowns, no auth required
            $method === 'GET' && $path === 'family/all' => (function () {
                self::authenticate(false); // Allow public access for dropdowns

                [$page, $limit] = self::getPagination(100, 1000); // Large limit for dropdowns

                $filters = self::getFilters(['branch_id', 'status', 'date_from', 'date_to']);

                $result = Family::getAll($page, $limit, $filters);
                self::paginated($result['data'], $result['pagination']['total'], $page, $limit);
            })(),

            // ADD MEMBER TO FAMILY
            $method === 'POST' && $pathParts[0] === 'family' && ($pathParts[1] ?? '') === 'addMember' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('manage_families');

                $familyId = self::getIdFromPath($pathParts, 2, 'Family ID');

                $payload = self::getPayload([
                    'member_id' => 'required|numeric',
                    'role'      => 'required|max:50'
                ]);

                $result = self::withTransaction(function () use ($familyId, $payload) {
                    return Family::addMember($familyId, (int)$payload['member_id'], $payload);
                });
                self::success($result, 'Member added to family');
            })(),

            // REMOVE MEMBER FROM FAMILY
            $method === 'DELETE' && $pathParts[0] === 'family' && ($pathParts[1] ?? '') === 'removeMember' && isset($pathParts[2], $pathParts[3]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('manage_families');

                $familyId = self::getIdFromPath($pathParts, 2, 'Family ID');
                $memberId = self::getIdFromPath($pathParts, 3, 'Member ID');

                $result = self::withTransaction(function () use ($familyId, $memberId) {
                    return Family::removeMember($familyId, $memberId);
                });
                self::success($result, 'Member removed from family');
            })(),

            // UPDATE MEMBER ROLE IN FAMILY
            $method === 'PUT' && $pathParts[0] === 'family' && ($pathParts[1] ?? '') === 'updateMemberRole' && isset($pathParts[2], $pathParts[3]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('manage_families');

                $familyId = self::getIdFromPath($pathParts, 2, 'Family ID');
                $memberId = self::getIdFromPath($pathParts, 3, 'Member ID');

                $payload = self::getPayload([
                    'role' => 'required|max:50'
                ]);

                $result = self::withTransaction(function () use ($familyId, $memberId, $payload) {
                    return Family::updateMemberRole($familyId, $memberId, $payload);
                });
                self::success($result, 'Member role updated');
            })(),

            // FALLBACK
            default => self::error('Family endpoint not found', 404),
        };
    }
}

// Dispatch
FamilyRoutes::handle();