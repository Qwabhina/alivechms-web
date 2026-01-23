<?php

/**
 * Audit Log API Routes
 *
 * Provides read-only access to audit logs for compliance and security monitoring.
 *
 * Features:
 * - Search audit logs with filters
 * - View entity-specific logs
 * - View user activity logs
 * - Export audit logs
 *
 * All operations require appropriate permissions.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/AuditLog.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class AuditRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 100, windowSeconds: 60);

      match (true) {
         // SEARCH AUDIT LOGS
         $method === 'GET' && $path === 'audit/search' => (function () {
            self::authenticate();
            self::authorize('settings.view'); // Audit logs require settings view permission

            [$page, $limit] = self::getPagination(50, 100);

            $filters = self::getFilters([
               'user_id',
               'action',
               'entity_type',
               'start_date',
               'end_date'
            ]);

            $result = AuditLog::search($filters, $page, $limit);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // GET ENTITY LOGS
         $method === 'GET' && $pathParts[0] === 'audit' && ($pathParts[1] ?? '') === 'entity' && isset($pathParts[2]) && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('settings.view');

            $entityType = $pathParts[2];
            $entityId = self::getIdFromPath($pathParts, 3, 'Entity ID');
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;

            $logs = AuditLog::getEntityLogs($entityType, $entityId, $limit);
            ResponseHelper::success(['data' => $logs]);
         })(),

         // GET USER ACTIVITY
         $method === 'GET' && $pathParts[0] === 'audit' && ($pathParts[1] ?? '') === 'user' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('settings.view');

            $userId = self::getIdFromPath($pathParts, 2, 'User ID');
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 100;

            $logs = AuditLog::getUserActivity($userId, $limit);
            ResponseHelper::success(['data' => $logs]);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Audit endpoint not found'),
      };
   }
}

// Dispatch
AuditRoutes::handle();
