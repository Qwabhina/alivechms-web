<?php

/**
 * Middleware Pipeline Example Route
 *
 * Demonstrates the middleware pipeline system with various middleware
 * configurations and usage patterns.
 *
 * @package  AliveChMS\Examples
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Http/EnhancedHttpRoute.php';

class MiddlewareExampleRoute extends EnhancedHttpRoute
{
   /**
    * Register middleware for this route
    */
   protected function registerMiddleware(): void
   {
      // Configure this route as an authenticated API route with logging
      $this->asAuthenticatedApiRoute([
         'optional' => false,  // Authentication is required
         'roles' => ['user', 'admin'],  // Allow users and admins
         'permissions' => ['api.access']  // Require API access permission
      ]);

      // Add custom rate limiting (30 requests per minute)
      $this->enableRateLimit(30, 1);

      // Enable detailed logging
      $this->enableLogging([
         'log_requests' => true,
         'log_responses' => true,
         'log_headers' => true,
         'log_body' => false,  // Don't log request/response bodies for security
         'sanitize_sensitive' => true
      ]);

      // Configure CORS for specific origins
      $this->enableCors([
         'allowed_origins' => ['https://app.alivechms.com', 'http://localhost:3000'],
         'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
         'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
         'allow_credentials' => true
      ]);
   }

   /**
    * Handle the HTTP request
    */
   public function handle(): Response
   {
      $method = self::request()->getMethod();

      return match ($method) {
         'GET' => $this->handleGet(),
         'POST' => $this->handlePost(),
         'PUT' => $this->handlePut(),
         'DELETE' => $this->handleDelete(),
         default => $this->error('Method not allowed', 405)
      };
   }

   /**
    * Handle GET request
    */
   private function handleGet(): Response
   {
      $user = self::getAuthenticatedUser();

      return $this->success([
         'message' => 'Middleware pipeline working!',
         'user' => $user ? $user['username'] ?? 'Unknown' : null,
         'middleware_count' => $this->getMiddleware()->count(),
         'middleware_order' => $this->getMiddlewareExecutionOrder(),
         'request_info' => [
            'method' => self::request()->getMethod(),
            'path' => self::request()->getPath(),
            'ip' => self::request()->ip(),
            'user_agent' => self::request()->userAgent(),
            'is_secure' => self::request()->isSecure(),
            'is_ajax' => self::request()->isAjax()
         ]
      ]);
   }

   /**
    * Handle POST request
    */
   private function handlePost(): Response
   {
      // Validate input
      $data = self::validate([
         'name' => 'required|string|max:100',
         'email' => 'required|email',
         'message' => 'required|string|max:1000'
      ]);

      $user = self::getAuthenticatedUser();

      // Simulate processing
      $result = [
         'id' => rand(1000, 9999),
         'name' => $data['name'],
         'email' => $data['email'],
         'message' => $data['message'],
         'created_by' => $user['id'] ?? null,
         'created_at' => date('c')
      ];

      return $this->success($result, 'Data processed successfully', 201);
   }

   /**
    * Handle PUT request
    */
   private function handlePut(): Response
   {
      $id = self::getIdFromRoute();

      $data = self::validate([
         'name' => 'string|max:100',
         'email' => 'email',
         'message' => 'string|max:1000'
      ]);

      $user = self::getAuthenticatedUser();

      $result = [
         'id' => $id,
         'updated_data' => $data,
         'updated_by' => $user['id'] ?? null,
         'updated_at' => date('c')
      ];

      return $this->success($result, 'Data updated successfully');
   }

   /**
    * Handle DELETE request
    */
   private function handleDelete(): Response
   {
      $id = self::getIdFromRoute();
      $user = self::getAuthenticatedUser();

      // Check if user has admin role for deletion
      if (!in_array('admin', $user['roles'] ?? [])) {
         return $this->forbidden('Only administrators can delete records');
      }

      $result = [
         'id' => $id,
         'deleted_by' => $user['id'] ?? null,
         'deleted_at' => date('c')
      ];

      return $this->success($result, 'Data deleted successfully');
   }
}

/**
 * Public API Route Example (no authentication required)
 */
class PublicApiRoute extends EnhancedHttpRoute
{
   protected function registerMiddleware(): void
   {
      // Configure as public API route
      $this->asApiRoute();

      // Higher rate limit for public endpoints
      $this->enableRateLimit(100, 1);
   }

   public function handle(): Response
   {
      return $this->success([
         'message' => 'Public API endpoint',
         'timestamp' => date('c'),
         'middleware_active' => $this->getMiddlewareExecutionOrder()
      ]);
   }
}

/**
 * Admin-Only Route Example
 */
class AdminOnlyRoute extends EnhancedHttpRoute
{
   protected function registerMiddleware(): void
   {
      // Configure as admin-only route
      $this->asAdminRoute();

      // Stricter rate limiting for admin endpoints
      $this->enableRateLimit(20, 1);
   }

   public function handle(): Response
   {
      $user = self::getAuthenticatedUser();

      return $this->success([
         'message' => 'Admin-only endpoint accessed',
         'admin_user' => $user['username'] ?? 'Unknown',
         'admin_permissions' => $user['permissions'] ?? [],
         'system_info' => [
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
         ]
      ]);
   }
}

/**
 * Conditional Middleware Route Example
 */
class ConditionalMiddlewareRoute extends EnhancedHttpRoute
{
   protected function registerMiddleware(): void
   {
      // Base API setup
      $this->enableCors()
         ->enableLogging();

      // Conditional authentication based on request path
      $conditionalAuth = $this->getMiddleware()->when(function (Request $request) {
         return str_contains($request->getPath(), 'protected');
      });

      $conditionalAuth->add(new AuthMiddleware(Application::resolve('Auth')));

      // Different rate limits based on authentication
      if (self::isAuthenticated()) {
         $this->enableRateLimit(100, 1); // Higher limit for authenticated users
      } else {
         $this->enableRateLimit(20, 1);  // Lower limit for anonymous users
      }
   }

   public function handle(): Response
   {
      $isProtected = str_contains(self::request()->getPath(), 'protected');
      $user = self::getAuthenticatedUser();

      return $this->success([
         'message' => $isProtected ? 'Protected endpoint' : 'Public endpoint',
         'authenticated' => $user !== null,
         'user' => $user ? $user['username'] ?? 'Unknown' : null,
         'path' => self::request()->getPath()
      ]);
   }
}
