<?php

/**
 * Example Route Using Dependency Injection
 *
 * Demonstrates how to use the new DI container in route classes
 * for better testability and reduced coupling.
 *
 * @package  AliveChMS\Examples
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRouteWithDI.php';

class DIExampleRoute extends BaseRouteWithDI
{
   public static function handle(): void
   {
      global $method, $path;

      match (true) {
         // Example: Get members using DI
         $method === 'GET' && $path === 'example/members' => (function () {
            // Authenticate using injected Auth service
            self::authenticate();

            // Get pagination parameters
            $pagination = self::getPagination();

            // Resolve Member service from container
            $memberService = Application::resolve('Member');

            // Use the service (this would be the new DI-aware version)
            $result = $memberService->getAll($pagination['page'], $pagination['limit']);

            // Return paginated response
            self::paginated(
               $result['data'],
               $result['pagination']['total'],
               $result['pagination']['page'],
               $result['pagination']['limit']
            );
         })(),

         // Example: Create member using DI
         $method === 'POST' && $path === 'example/members' => (function () {
            // Authenticate and authorize
            self::authenticate();
            self::authorize('manage_members');

            // Validate payload using injected Validator
            $payload = self::getPayload([
               'first_name' => 'required|max:50',
               'family_name' => 'required|max:50',
               'email_address' => 'required|email'
            ]);

            // Start transaction using injected ORM
            self::beginTransaction();

            try {
               // Resolve Member service
               $memberService = Application::resolve('Member');
               $result = $memberService->register($payload);

               // Log using injected AuditLog
               $auditLog = Application::resolve('AuditLog');
               $auditLog->logMember('create', $result['mbr_id']);

               self::commitTransaction();
               self::success($result, 'Member created successfully', 201);
            } catch (Exception $e) {
               self::rollbackTransaction();
               self::error('Failed to create member: ' . $e->getMessage(), 500);
            }
         })(),

         default => self::error('Endpoint not found', 404)
      };
   }
}

// Usage example:
// DIExampleRoute::handle();