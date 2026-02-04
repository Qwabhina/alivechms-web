<?php

/**
 * Family Management Service
 *
 * Handles orchestration of family units and delegates data persistence to FamilyRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\People\FamilyRepository;
use AliveChMS\Core\People\MemberRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Infrastructure\Cache;
use Exception;

class Family
{
    /**
     * Create a new family unit
     */
    public static function create(array $data): array
    {
        $repo = new FamilyRepository();

        Helpers::validateInput($data, [
            'family_name' => 'required|max:100',
            'head_id'     => 'numeric|nullable',
            'branch_id'   => 'required|numeric'
        ]);

        $headId = !empty($data['head_id']) ? (int)$data['head_id'] : null;
        $branchId = (int)$data['branch_id'];

        $repo->beginTransaction();
        try {
            $familyId = $repo->create([
                'FamilyName'        => trim($data['family_name']),
                'HeadOfHouseholdID' => $headId,
                'BranchID'          => $branchId,
                'CreatedAt'         => date('Y-m-d H:i:s')
            ]);

            if ($headId) {
                // Assign head to family
                $repo->assignMember($headId, $familyId);
            }

            $repo->commit();
            Helpers::logError("New family created: ID $familyId");
            return ['status' => 'success', 'family_id' => $familyId];
        } catch (Exception $e) {
            $repo->rollBack();
            throw $e;
        }
    }

    public static function update(int $familyId, array $data): array
    {
        $repo = new FamilyRepository();
        $updates = [];

        if (isset($data['family_name'])) $updates['FamilyName'] = trim($data['family_name']);
        if (isset($data['head_id']))
            $updates['HeadOfHouseholdID'] = (int) $data['head_id'];
        if (isset($data['branch_id']))
            $updates['BranchID'] = (int) $data['branch_id'];

        if (empty($updates))
            ResponseHelper::error('No updates provided', 400);

        $repo->update($familyId, $updates);

        Cache::invalidateTag('family_' . $familyId);
        return ['status' => 'success'];
    }

    public static function softDelete(int $familyId): array
    {
        $repo = new FamilyRepository();
        if ($repo->hasActiveMembers($familyId)) {
            ResponseHelper::error('Cannot delete family with active members', 400);
        }

        $repo->delete($familyId);
        Cache::invalidateTag('family_' . $familyId);
        return ['status' => 'success'];
    }

    public static function get(int $familyId): array
    {
        $repo = new FamilyRepository();
        $family = $repo->findById($familyId);

        if (!$family)
            ResponseHelper::error('Family not found', 404);

        $family['members'] = $repo->getMembers($familyId);
        return $family;
    }

    public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $repo = new FamilyRepository();
        $offset = ($page - 1) * $limit;
        $result = $repo->findAll($limit, $offset, $filters);

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

    public static function addMember(int $familyId, int $memberId): array
    {
        $repo = new FamilyRepository();
        $repo->assignMember($memberId, $familyId);
        Cache::invalidateTag('family_' . $familyId);
        return ['status' => 'success'];
    }

    public static function removeMember(int $familyId, int $memberId): array
    {
        $repo = new FamilyRepository();
        $repo->assignMember($memberId, null);
        Cache::invalidateTag('family_' . $familyId);
        return ['status' => 'success'];
    }

    public static function updateMemberRole(int $familyId, int $memberId, string $role): array
    {
        $repo = new FamilyRepository();
        $repo->updateMemberRole($familyId, $memberId, $role);
        Cache::invalidateTag('family_' . $familyId);
        return ['status' => 'success'];
    }
}