<?php

/**
 * Member Milestone Management
 *
 * Orchestrates life events and delegates persistence to MilestoneRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\People\MilestoneRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;

class MemberMilestone
{
   public static function create(array $data): array
   {
      $repo = new MilestoneRepository();

      Helpers::validateInput($data, [
         'member_id' => 'required|numeric',
         'milestone_type_id' => 'required|numeric',
         'milestone_date' => 'required|date'
      ]);

      $milestoneId = $repo->create([
         'MbrID' => (int) $data['member_id'],
         'MilestoneTypeID' => (int) $data['milestone_type_id'],
         'MilestoneDate' => $data['milestone_date'],
         'Location' => $data['location'] ?? null,
         'OfficiatingPastor' => $data['officiating_pastor'] ?? null,
         'RecordedBy' => Auth::getCurrentUserId(),
         'RecordedAt' => date('Y-m-d H:i:s'),
         'Deleted' => 0
      ]);

      return ['status' => 'success', 'milestone_id' => $milestoneId];
   }

   public static function get(int $milestoneId): array
   {
      $repo = new MilestoneRepository();
      $m = $repo->findById($milestoneId);
      if (!$m)
         ResponseHelper::error('Milestone not found', 404);
      return $m;
   }

   public static function getStats(?int $year = null): array
   {
      $repo = new MilestoneRepository();
      return $repo->getStats($year);
   }

   public static function getAll(int $page = 1, int $limit = 25, array $filters = []): array
   {
      $repo = new MilestoneRepository();
      return $repo->getAll($page, $limit, $filters);
   }
}
