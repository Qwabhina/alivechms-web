<?php

/**
 * Authentication Middleware
 *
 * Handles authentication for protected routes using the existing Auth system.
 * Validates tokens and ensures user authentication.
 *
 * @package  AliveChMS\Core\Http\Middleware
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../../Auth.php';

class AuthMiddleware extends Middleware
{
   private $auth;
   private array $config;

   public function __construct($auth = null, array $config = [])
   {
      $this->auth = $auth ?? new Auth();
      $this->config = array_merge([
         'token_header' => 'Authorization',
         'token_prefix' => 'Bearer ',
         'optional' => false,
         'roles' => [],
         'permissions' => []
      ], $config);
   }

   public function handle(Request $request, callable $next): Response
   {
      $token = $this->extractToken($request);

      if (!$token) {
         if ($this->config['optional']) {
            return $next($request);
         }
         return Response::unauthorized('Authentication token required');
      }

      try {
         $user = $this->auth->validateToken($token);

         if (!$user) {
            return Response::unauthorized('Invalid authentication token');
         }

         // Check roles if specified
         if (!empty($this->config['roles']) && !$this->hasRequiredRole($user)) {
            return Response::forbidden('Insufficient role privileges');
         }

         // Check permissions if specified
         if (!empty($this->config['permissions']) && !$this->hasRequiredPermissions($user)) {
            return Response::forbidden('Insufficient permissions');
         }

         // Add user to request for downstream use
         $request->setRouteParams(array_merge(
            $request->routeParams(),
            ['authenticated_user' => $user]
         ));

         return $next($request);
      } catch (Exception $e) {
         return Response::unauthorized('Authentication failed: ' . $e->getMessage());
      }
   }

   public function getPriority(): int
   {
      return 30; // Execute after rate limiting
   }

   /**
    * Extract token from request
    */
   private function extractToken(Request $request): ?string
   {
      $header = $request->header($this->config['token_header']);

      if (!$header) {
         return null;
      }

      if (str_starts_with($header, $this->config['token_prefix'])) {
         return substr($header, strlen($this->config['token_prefix']));
      }

      return $header;
   }

   /**
    * Check if user has required role
    */
   private function hasRequiredRole($user): bool
   {
      if (empty($this->config['roles'])) {
         return true;
      }

      $userRoles = $user['roles'] ?? [];

      foreach ($this->config['roles'] as $requiredRole) {
         if (in_array($requiredRole, $userRoles)) {
            return true;
         }
      }

      return false;
   }

   /**
    * Check if user has required permissions
    */
   private function hasRequiredPermissions($user): bool
   {
      if (empty($this->config['permissions'])) {
         return true;
      }

      $userPermissions = $user['permissions'] ?? [];

      foreach ($this->config['permissions'] as $requiredPermission) {
         if (!in_array($requiredPermission, $userPermissions)) {
            return false;
         }
      }

      return true;
   }

   /**
    * Create middleware for specific roles
    */
   public static function requireRoles(array $roles): self
   {
      return new self(null, ['roles' => $roles]);
   }

   /**
    * Create middleware for specific permissions
    */
   public static function requirePermissions(array $permissions): self
   {
      return new self(null, ['permissions' => $permissions]);
   }

   /**
    * Create optional authentication middleware
    */
   public static function optional(): self
   {
      return new self(null, ['optional' => true]);
   }
}
