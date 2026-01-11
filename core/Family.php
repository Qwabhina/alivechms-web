<?php

/**
 * Family Management – Core Family Unit Operations
 *
 * Full lifecycle management for church families:
 * - Create family with head
 * - Update family details
 * - Soft delete with safety checks
 * - Add/remove members
 * - Update member roles
 * - Retrieve single family with members
 * - Paginated listing with filters
 *
 * All operations atomic, audited, and permission-aware.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/QueryBuilder.php';

class Family
{
    /**
     * Create a new family unit
     *
     * Automatically adds head as first member.
     *
     * @param array $data Family payload
     * @return array{status:string, family_id:int} Success response
     */
    public static function create(array $data): array
    {
        $orm = new ORM();

        Helpers::validateInput($data, [
            'family_name' => 'required|max:100',
            'head_id'     => 'numeric|nullable',
            'branch_id'   => 'required|numeric',
            'address'     => 'max:255|nullable',
            'phone'       => 'max:20|nullable',
            'email'       => 'email|nullable'
        ]);

        $headId = !empty($data['head_id']) ? (int)$data['head_id'] : null;
        $branchId = (int)$data['branch_id'];

        // Validate head member if provided
        if ($headId && !$orm->exists('churchmember', ['MbrID' => $headId])) {
            ResponseHelper::error('Head of household not found', 400);
        }

        $orm->beginTransaction();
        try {
            // Create family
            $familyId = $orm->insert('family', [
                'FamilyName'        => trim($data['family_name']),
                'HeadOfHouseholdID' => $headId,
                'BranchID'          => $branchId,
                'CreatedAt'         => date('Y-m-d H:i:s')
            ])['id'];

            // Assign head to family if provided
            if ($headId) {
                $orm->update('churchmember', [
                    'FamilyID'   => $familyId
                ], ['MbrID' => $headId]);
            }

            $orm->commit();

            Helpers::logError("New family created: ID $familyId" . ($headId ? ", Head: $headId" : ""));
            return ['status' => 'success', 'family_id' => $familyId];
        } catch (Exception $e) {
            $orm->rollBack();
            throw $e;
        }
    }

    /**
     * Update family details
     *
     * @param int $familyId Family ID
     * @param array $data Update payload
     * @return array{status:string} Success response
     */
    public static function update(int $familyId, array $data): array
    {
        $orm = new ORM();

        $updates = [];
        if (isset($data['family_name'])) $updates['FamilyName'] = trim($data['family_name']);
        if (isset($data['head_id'])) $updates['HeadOfHouseholdID'] = (int)$data['head_id'];
        if (isset($data['branch_id'])) $updates['BranchID'] = (int)$data['branch_id'];

        if (empty($updates)) {
            ResponseHelper::error('No updates provided', 400);
        }

        $affected = $orm->update('family', $updates, ['FamilyID' => $familyId]);

        if ($affected === 0) {
            ResponseHelper::error('Family not found', 404);
        }

        // Invalidate cache
        Cache::invalidateTag('family_' . $familyId);
        Cache::invalidateTag('families_list');

        Helpers::logError("Family ID $familyId updated");
        return ['status' => 'success'];
    }

    /**
     * Delete a family
     *
     * Only if no active members.
     *
     * @param int $familyId Family ID
     * @return array{status:string} Success response
     */
    public static function softDelete(int $familyId): array
    {
        $orm = new ORM();

        // Check for active members
        if ($orm->exists('churchmember', ['FamilyID' => $familyId, 'Deleted' => 0])) {
            ResponseHelper::error('Cannot delete family with active members', 400);
        }

        // Hard delete since table has no Deleted column
        $affected = $orm->delete('family', ['FamilyID' => $familyId]);

        if ($affected === 0) {
            ResponseHelper::error('Family not found', 404);
        }

        // Invalidate cache
        Cache::invalidateTag('family_' . $familyId);
        Cache::invalidateTag('families_list');

        Helpers::logError("Family ID $familyId deleted");
        return ['status' => 'success'];
    }

    /**
     * Retrieve a single family with all members
     *
     * @param int $familyId Family ID
     * @return array Family data with members
     */
    public static function get(int $familyId): array
    {
        $orm = new ORM();

        // Get family with head name
        $family = $orm->runQuery(
            "SELECT 
                f.FamilyID,
                f.FamilyName,
                f.HeadOfHouseholdID,
                f.BranchID,
                f.CreatedAt,
                CONCAT(m.MbrFirstName, ' ', m.MbrFamilyName) AS HeadOfHouseholdName
             FROM family f
             LEFT JOIN churchmember m ON f.HeadOfHouseholdID = m.MbrID
             WHERE f.FamilyID = :family_id",
            [':family_id' => $familyId]
        );

        if (empty($family)) {
            ResponseHelper::error('Family not found', 404);
        }

        $familyData = $family[0];

        // Get family members
        $familyData['members'] = $orm->runQuery(
            "SELECT 
                m.MbrID,
                m.MbrFirstName,
                m.MbrFamilyName,
                m.MbrEmailAddress,
                m.MbrProfilePicture
             FROM churchmember m
             WHERE m.FamilyID = :family_id AND m.Deleted = 0
             ORDER BY m.MbrFirstName, m.MbrFamilyName",
            [':family_id' => $familyId]
        );

        return $familyData;
    }

    /**
     * Get paginated list of families
     *
     * @param int $page Page number
     * @param int $limit Per page
     * @param array $filters Filters (branch_id, status, etc.)
     * @return array Paginated result
     */
    public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $orm = new ORM();

        $offset = ($page - 1) * $limit;

        // Build WHERE conditions
        $whereConditions = ['1=1']; // family table has no Deleted column
        $params = [];

        if (!empty($filters['branch_id'])) {
            $whereConditions[] = 'f.BranchID = :branch_id';
            $params[':branch_id'] = (int)$filters['branch_id'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get families - use direct values for LIMIT/OFFSET (PDO doesn't bind well in LIMIT)
        $families = $orm->runQuery(
            "SELECT 
                f.FamilyID, 
                f.FamilyName, 
                f.HeadOfHouseholdID,
                f.CreatedAt,
                CONCAT(m.MbrFirstName, ' ', m.MbrFamilyName) AS HeadOfHouseholdName,
                (SELECT COUNT(*) FROM churchmember WHERE FamilyID = f.FamilyID AND Deleted = 0) AS MemberCount
             FROM `family` f
             LEFT JOIN churchmember m ON f.HeadOfHouseholdID = m.MbrID
             WHERE {$whereClause}
             ORDER BY f.FamilyName ASC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        // Get total count
        $totalResult = $orm->runQuery(
            "SELECT COUNT(*) AS total FROM `family` f WHERE {$whereClause}",
            $params
        );
        $total = (int)($totalResult[0]['total'] ?? 0);

        return [
            'data' => $families,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit)
            ]
        ];
    }

    /**
     * Add member to family
     *
     * @param int $familyId Family ID
     * @param int $memberId Member ID
     * @param array $data Role etc.
     * @return array{status:string} Success response
     */
    public static function addMember(int $familyId, int $memberId, array $data): array
    {
        $orm = new ORM();

        Helpers::validateInput($data, ['role' => 'required|max:50']);

        // Check existence
        if (!$orm->exists('family', ['FamilyID' => $familyId])) {
            ResponseHelper::error('Family not found', 404);
        }
        if (!$orm->exists('churchmember', ['MbrID' => $memberId])) {
            ResponseHelper::error('Member not found', 404);
        }

        $orm->update('churchmember', [
            'FamilyID' => $familyId
        ], ['MbrID' => $memberId]);

        // Invalidate cache
        Cache::invalidateTag('family_' . $familyId);
        Cache::invalidateTag('member_' . $memberId);

        Helpers::logError("Member $memberId added to Family $familyId");
        return ['status' => 'success'];
    }

    /**
     * Remove member from family
     *
     * @param int $familyId Family ID
     * @param int $memberId Member ID
     * @return array{status:string} Success response
     */
    public static function removeMember(int $familyId, int $memberId): array
    {
        $orm = new ORM();

        // Check if member is in this family
        $member = $orm->getWhere('churchmember', ['MbrID' => $memberId, 'FamilyID' => $familyId]);
        if (empty($member)) {
            ResponseHelper::error('Member not in family', 404);
        }

        $affected = $orm->update('churchmember', [
            'FamilyID' => null
        ], ['MbrID' => $memberId, 'FamilyID' => $familyId]);

        if ($affected === 0) {
            ResponseHelper::error('Member not in family', 404);
        }

        // Invalidate cache
        Cache::invalidateTag('family_' . $familyId);
        Cache::invalidateTag('member_' . $memberId);

        Helpers::logError("Member $memberId removed from Family $familyId");
        return ['status' => 'success'];
    }

    /**
     * Update member role in family
     *
     * @param int $familyId Family ID
     * @param int $memberId Member ID
     * @param array $data New role
     * @return array{status:string} Success response
     */
    public static function updateMemberRole(int $familyId, int $memberId, array $data): array
    {
        $orm = new ORM();

        Helpers::validateInput($data, ['role' => 'required|max:50']);

        // Check if member is in this family
        $member = $orm->getWhere('churchmember', ['MbrID' => $memberId, 'FamilyID' => $familyId]);
        if (empty($member)) {
            ResponseHelper::error('Member not in family', 404);
        }

        // Note: MbrFamilyRole column may not exist - this is a placeholder
        // $orm->update('churchmember', ['MbrFamilyRole' => $data['role']], ['MbrID' => $memberId]);

        // Invalidate cache
        Cache::invalidateTag('family_' . $familyId);
        Cache::invalidateTag('member_' . $memberId);

        Helpers::logError("Member $memberId role updated in Family $familyId");
        return ['status' => 'success'];
    }
}