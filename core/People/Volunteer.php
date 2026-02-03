<?php

/**
 * Volunteer Management Service
 *
 * Orchestrates volunteer service and delegates data operations to VolunteerRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\People\VolunteerRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use Exception;

class Volunteer
{
   public static function getRoles(): array
   {
      $repo = new VolunteerRepository();
      return $repo->getRoles();
   }

   public static function createRole(array $data): array
   {
      $repo = new VolunteerRepository();
      Helpers::validateInput($data, ['name' => 'required|max:100']);

      $roleId = $repo->createRole([
         'RoleName' => trim($data['name']),
         'Description' => $data['description'] ?? null
      ]);

      return ['status' => 'success', 'role_id' => $roleId];
   }

   public static function assignToEvent(int $eventId, array $volunteers): array
   {
      $repo = new VolunteerRepository();
      $repo->beginTransaction();
      try {
         foreach ($volunteers as $v) {
            $repo->assignToEvent([
               'EventID' => $eventId,
               'MbrID' => $v['member_id'],
               'VolunteerRoleID' => $v['role_id'] ?? null,
               'AssignedBy' => Auth::getCurrentUserId(),
               'Status' => 'Pending',
               'AssignedAt' => date('Y-m-d H:i:s')
            ]);
            }
         $repo->commit();
         return ['status' => 'success'];
      } catch (Exception $e) {
         $repo->rollBack();
         throw $e;
      }
   }

   public static function updateStatus(int $assignmentId, string $status): array
   {
      $repo = new VolunteerRepository();
      $repo->updateStatus($assignmentId, $status);
      return ['status' => 'success'];
   }
}
