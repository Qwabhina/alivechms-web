<?php

/**
 * Communication & Notification System
 *
 * Orchestrates message delivery and delegates data operations to CommunicationRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\Operations\CommunicationRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use Exception;

class Communication
{
   /**
    * Send a notification/message to one member or an entire group
    */
   public static function send(array $data): array
   {
      $repo = new CommunicationRepository();

      Helpers::validateInput($data, [
         'title'   => 'required|max:200',
         'message' => 'required',
         'channel' => 'required|in:InApp,SMS,Email'
      ]);

      if (empty($data['member_id']) && empty($data['group_id'])) {
         ResponseHelper::error('Either member_id or group_id is required', 400);
      }

      if (!empty($data['member_id']) && !empty($data['group_id'])) {
         ResponseHelper::error('Cannot specify both member_id and group_id', 400);
      }

      $sentBy = Auth::getCurrentUserId();

      $repo->beginTransaction();
      try {
         $commId = $repo->create([
            'Title' => $data['title'],
            'Message' => $data['message'],
            'SentBy' => $sentBy,
            'TargetMemberID' => !empty($data['member_id']) ? (int) $data['member_id'] : null,
            'TargetGroupID' => !empty($data['group_id']) ? (int) $data['group_id'] : null,
            'Channel' => $data['channel'],
            'Status' => 'Pending',
            'CreatedAt' => date('Y-m-d H:i:s')
         ]);

         if (!empty($data['member_id'])) {
            $repo->createDelivery([
               'CommID' => $commId,
               'MbrID' => (int) $data['member_id'],
               'Channel' => $data['channel'],
               'Status' => 'Pending'
            ]);
         } else {
            $members = $repo->getGroupMembers((int) $data['group_id']);
            foreach ($members as $member) {
               $repo->createDelivery([
                  'CommID' => $commId,
                  'MbrID' => (int) $member['MbrID'],
                  'Channel' => $data['channel'],
                  'Status' => 'Pending'
               ]);
            }
         }

         $repo->commit();
         Helpers::logError("Communication queued: ID $commId");
         return ['status' => 'success', 'communication_id' => $commId];
      } catch (Exception $e) {
         $repo->rollBack();
         throw $e;
      }
   }

   /**
    * Retrieve current user's unread + read notifications
    */
   public static function getMyNotifications(int $page = 1, int $limit = 20): array
   {
      $repo = new CommunicationRepository();
      $userId = Auth::getCurrentUserId();
      $offset = ($page - 1) * $limit;
      $result = $repo->getDeliveriesForUser($userId, $limit, $offset);

      return [
         'data' => $result['data'],
         'pagination' => [
            'page'   => $page,
            'limit'  => $limit,
            'total' => (int) $result['total'],
            'pages' => (int) ceil($result['total'] / $limit)
         ]
      ];
   }

   /**
    * Mark a notification as read/delivered
    */
   public static function markAsRead(int $commId): array
   {
      $repo = new CommunicationRepository();
      $userId = Auth::getCurrentUserId();

      $affected = $repo->updateDelivery($commId, $userId, [
         'Status'      => 'Sent',
         'DeliveredAt' => date('Y-m-d H:i:s')
      ]);

      if ($affected === 0) {
         ResponseHelper::error('Notification not found or already read', 404);
      }

      return ['status' => 'success'];
   }
}