<?php

/**
 * Example Route Using Request/Response Objects
 *
 * Demonstrates the new HTTP wrapper classes for cleaner,
 * more testable route handling.
 *
 * @package  AliveChMS\Examples
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Http/BaseHttpRoute.php';

class HttpExampleRoute extends BaseHttpRoute
{
   public static function handle(): void
   {
      $request = self::request();
      $method = $request->getMethod();
      $path = $request->getPath();

      match (true) {
         // GET /example/members - List members with clean request handling
         $method === 'GET' && $path === 'example/members' => self::executeWithExceptionHandling(function () {
            // Authenticate using clean API
            self::authenticate();

            // Get pagination from query parameters
            $pagination = self::getPagination();

            // Get filters from request
            $request = self::request();
            $filters = $request->only(['status', 'family_id', 'search']);

            // Resolve service and get data
            $memberService = Application::resolve('Member');
            $result = $memberService->getAll($pagination['page'], $pagination['limit'], $filters);

            // Return clean paginated response
            self::paginated(
               $result['data'],
               $result['pagination']['total'],
               $result['pagination']['page'],
               $result['pagination']['limit']
            )->sendAndExit();
         }),

         // POST /example/members - Create member with validation
         $method === 'POST' && $path === 'example/members' => self::executeWithExceptionHandling(function () {
            // Authenticate and authorize
            self::authenticate();
            self::authorize('manage_members');

            // Validate input using clean API
            $data = self::validate([
               'first_name' => 'required|max:50',
               'family_name' => 'required|max:50',
               'email_address' => 'required|email',
               'phone_numbers' => 'nullable',
               'gender' => 'in:Male,Female,Other|nullable'
            ]);

            // Handle file upload if present
            $request = self::request();
            if ($request->hasFile('profile_picture')) {
               $file = $request->file('profile_picture');
               // File upload logic would go here
               $data['profile_picture'] = 'uploads/members/example.jpg';
            }

            // Start transaction
            self::beginTransaction();

            try {
               // Create member
               $memberService = Application::resolve('Member');
               $result = $memberService->register($data);

               // Log the action
               $auditLog = Application::resolve('AuditLog');
               $auditLog->logMember('create', $result['mbr_id']);

               self::commitTransaction();

               // Return created response
               self::created($result, 'Member created successfully')->sendAndExit();
            } catch (Exception $e) {
               self::rollbackTransaction();
               throw $e;
            }
         }),

         // GET /example/members/{id} - Get single member
         $method === 'GET' && preg_match('#^example/members/(\d+)$#', $path, $matches) => self::executeWithExceptionHandling(function () use ($matches) {
            self::authenticate();

            $memberId = (int)$matches[1];

            $memberService = Application::resolve('Member');
            $member = $memberService->get($memberId);

            self::success($member)->sendAndExit();
         }),

         // PUT /example/members/{id} - Update member
         $method === 'PUT' && preg_match('#^example/members/(\d+)$#', $path, $matches) => self::executeWithExceptionHandling(function () use ($matches) {
            self::authenticate();
            self::authorize('manage_members');

            $memberId = (int)$matches[1];

            // Validate update data
            $data = self::validate([
               'first_name' => 'required|max:50',
               'family_name' => 'required|max:50',
               'email_address' => 'required|email'
            ]);

            self::beginTransaction();

            try {
               $memberService = Application::resolve('Member');
               $result = $memberService->update($memberId, $data);

               // Log the update
               $auditLog = Application::resolve('AuditLog');
               $auditLog->logMember('update', $memberId, $data);

               self::commitTransaction();

               self::success($result, 'Member updated successfully')->sendAndExit();
            } catch (Exception $e) {
               self::rollbackTransaction();
               throw $e;
            }
         }),

         // DELETE /example/members/{id} - Delete member
         $method === 'DELETE' && preg_match('#^example/members/(\d+)$#', $path, $matches) => self::executeWithExceptionHandling(function () use ($matches) {
            self::authenticate();
            self::authorize('manage_members');

            $memberId = (int)$matches[1];

            $memberService = Application::resolve('Member');
            $result = $memberService->delete($memberId);

            // Log the deletion
            $auditLog = Application::resolve('AuditLog');
            $auditLog->logMember('delete', $memberId);

            self::success($result, 'Member deleted successfully')->sendAndExit();
         }),

         // OPTIONS - CORS preflight
         $method === 'OPTIONS' => (function () {
            Response::make('', 204)
               ->cors()
               ->sendAndExit();
         })(),

         // Default - Not found
         default => self::notFound('Endpoint not found')->sendAndExit()
      };
   }
}

// Usage:
// HttpExampleRoute::handle();