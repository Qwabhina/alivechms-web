<?php

/**
 * Audit Logging Service
 * 
 * Tracks sensitive operations and changes. Delegates persistence to InfrastructureRepository.
 *
 * @package AliveChMS\Core
 * @version 2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\System;

use AliveChMS\Core\Identity\Auth;
use Exception;

class AuditLog
{
   public static function log(
      string $action,
      string $entity,
      int $entityId,
      array $changes = [],
      array $metadata = []
   ): void {
      $repo = new InfrastructureRepository();

      try {
         $userId = Auth::getCurrentUserId();
      } catch (Exception $e) {
         $userId = null;
      }

      $repo->insertAuditLog([
         'user_id' => $userId,
         'action' => $action,
         'entity_type' => $entity,
         'entity_id' => $entityId,
         'changes' => !empty($changes) ? json_encode($changes) : null,
         'metadata' => !empty($metadata) ? json_encode($metadata) : null,
         'ip_address' => Helpers::getClientIp(),
         'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
         'created_at' => date('Y-m-d H:i:s')
      ]);
   }

   public static function logLogin(string $username, bool $success, ?int $userId = null): void
   {
      $repo = new InfrastructureRepository();
      $repo->insertLoginLog([
         'user_id' => $userId,
         'username' => $username,
         'success' => $success ? 1 : 0,
         'ip_address' => Helpers::getClientIp(),
         'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
         'created_at' => date('Y-m-d H:i:s')
      ]);
   }

   public static function search(array $filters, int $page, int $limit): array
   {
      $repo = new InfrastructureRepository();
      return $repo->searchAuditLogs($filters, $page, $limit);
   }

   public static function getEntityLogs(string $entityType, int $entityId, int $limit): array
   {
      $repo = new InfrastructureRepository();
      return $repo->getEntityLogs($entityType, $entityId, $limit);
   }

   public static function getUserActivity(int $userId, int $limit): array
   {
      $repo = new InfrastructureRepository();
      return $repo->getUserActivity($userId, $limit);
   }
}
