<?php

/**
 * Visitor Management Service
 *
 * Handles visitor tracking and conversion. Delegates data operations to VisitorRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\People\VisitorRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use Exception;

class Visitor
{
   public static function create(array $data): array
   {
      $repo = new VisitorRepository();

      Helpers::validateInput($data, [
         'first_name' => 'required|max:100',
         'last_name' => 'required|max:100',
         'branch_id' => 'required|numeric',
         'visit_date' => 'required|date'
      ]);

      $visitorId = $repo->create([
         'FirstName' => trim($data['first_name']),
         'LastName' => trim($data['last_name']),
         'EmailAddress' => $data['email'] ?? null,
         'PhoneNumber' => $data['phone'] ?? null,
         'FirstVisitDate' => $data['visit_date'],
         'Source' => $data['source'] ?? 'Walk-in',
         'BranchID' => (int) $data['branch_id'],
         'CreatedAt' => date('Y-m-d H:i:s')
      ]);

      return ['status' => 'success', 'visitor_id' => $visitorId];
   }

   public static function get(int $visitorId): array
   {
      $repo = new VisitorRepository();
      $visitor = $repo->findById($visitorId);
      if (!$visitor)
         ResponseHelper::error('Visitor not found', 404);
      return $visitor;
   }

   public static function getAll(int $page = 1, int $limit = 25, array $filters = []): array
   {
      $repo = new VisitorRepository();
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

   public static function convertToMember(int $visitorId, array $data): array
   {
      $repo = new VisitorRepository();
      $visitor = $repo->findById($visitorId);

      if (!$visitor)
         ResponseHelper::error('Visitor not found', 404);
      if (!empty($visitor['ConvertedToMemberID'])) {
         ResponseHelper::error('Visitor already converted', 400);
      }

      $repo->beginTransaction();
      try {
         // Logic to create member (simplified for brevity)
         // Ideally calls MemberRepository
         $memberId = 0; // Simulated

         $repo->update($visitorId, [
            'ConvertedToMemberID' => $memberId,
            'ConvertedAt' => date('Y-m-d H:i:s')
         ]);

         $repo->commit();
         return ['status' => 'success', 'member_id' => $memberId];
      } catch (Exception $e) {
         $repo->rollBack();
         throw $e;
      }
   }

   public static function update(int $visitorId, array $data): array
   {
      $repo = new VisitorRepository();
      $visitor = $repo->findById($visitorId);
      if (!$visitor)
         ResponseHelper::error('Visitor not found', 404);
      $repo->update($visitorId, $data);
      return ['status' => 'success'];
   }

   public static function delete(int $visitorId): array
   {
      $repo = new VisitorRepository();
      $visitor = $repo->findById($visitorId);
      if (!$visitor)
         ResponseHelper::error('Visitor not found', 404);
      $repo->delete($visitorId);
      return ['status' => 'success'];
   }

   public static function recordReturnVisit(int $visitorId, array $data): array
   {
      $repo = new VisitorRepository();
      $visitor = $repo->findById($visitorId);
      if (!$visitor)
         ResponseHelper::error('Visitor not found', 404);
      $repo->update($visitorId, $data);
      return ['status' => 'success'];
   }

   public static function assignFollowUp(int $visitorId, int $memberId): array
   {
      $repo = new VisitorRepository();
      $visitor = $repo->findById($visitorId);
      if (!$visitor)
         ResponseHelper::error('Visitor not found', 404);
      $repo->assignFollowUp($visitorId, $memberId);
      return ['status' => 'success'];
   }

   public static function getStats(array $filters): array
   {
      $repo = new VisitorRepository();
      return $repo->getStats($filters);
   }
}
