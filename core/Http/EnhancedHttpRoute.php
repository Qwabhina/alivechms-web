<?php

/**
 * Enhanced HTTP Route Class
 *
 * Enhanced base class for HTTP routes with middleware pipeline support.
 * Provides clean API for handling HTTP requests with middleware integration.
 *
 * Features:
 * - Middleware pipeline support
 * - Request/Response wrapper integration
 * - Input validation
 * - Error handling
 * - JSON response helpers
 * - Authentication and authorization
 *
 * @package  AliveChMS\Core\Http
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseHttpRoute.php';
require_once __DIR__ . '/MiddlewarePipeline.php';
require_once __DIR__ . '/Middleware/CorsMiddleware.php';
require_once __DIR__ . '/Middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/Middleware/LoggingMiddleware.php';

abstract class EnhancedHttpRoute extends BaseHttpRoute
{
   protected MiddlewarePipeline $middleware;
   protected bool $middlewareRegistered = false;

   public function __construct()
   {
      $this->middleware = new MiddlewarePipeline();
      $this->registerMiddleware();
      $this->middlewareRegistered = true;
   }

   /**
    * Handle the HTTP request
    *
    * @return Response
    */
   abstract public function handle(): Response;

   /**
    * Register middleware for this route
    * Override in subclasses to add specific middleware
    */
   protected function registerMiddleware(): void
   {
      // Override in subclasses to add middleware
   }

   /**
    * Add middleware to the pipeline
    *
    * @param Middleware|string $middleware Middleware instance or class name
    * @return self
    */
   protected function addMiddleware($middleware): self
   {
      $this->middleware->add($middleware);
      return $this;
   }

   /**
    * Add multiple middleware at once
    *
    * @param array $middlewares Array of middleware
    * @return self
    */
   protected function addMiddlewares(array $middlewares): self
   {
      $this->middleware->addMany($middlewares);
      return $this;
   }

   /**
    * Add CORS middleware with configuration
    *
    * @param array $config CORS configuration
    * @return self
    */
   protected function enableCors(array $config = []): self
   {
      return $this->addMiddleware(new CorsMiddleware($config));
   }

   /**
    * Add rate limiting middleware
    *
    * @param int $maxAttempts Maximum attempts
    * @param int $decayMinutes Decay time in minutes
    * @return self
    */
   protected function enableRateLimit(int $maxAttempts = 60, int $decayMinutes = 1): self
   {
      $rateLimiter = Application::resolve('RateLimiter');
      return $this->addMiddleware(new RateLimitMiddleware($rateLimiter, $maxAttempts, $decayMinutes));
   }

   /**
    * Add authentication middleware
    *
    * @param array $config Auth configuration
    * @return self
    */
   protected function requireAuth(array $config = []): self
   {
      $auth = Application::resolve('Auth');
      return $this->addMiddleware(new AuthMiddleware($auth, $config));
   }

   /**
    * Add optional authentication middleware
    *
    * @return self
    */
   protected function optionalAuth(): self
   {
      return $this->requireAuth(['optional' => true]);
   }

   /**
    * Require specific roles
    *
    * @param array $roles Required roles
    * @return self
    */
   protected function requireRoles(array $roles): self
   {
      return $this->addMiddleware(AuthMiddleware::requireRoles($roles));
   }

   /**
    * Require specific permissions
    *
    * @param array $permissions Required permissions
    * @return self
    */
   protected function requirePermissions(array $permissions): self
   {
      return $this->addMiddleware(AuthMiddleware::requirePermissions($permissions));
   }

   /**
    * Enable request/response logging
    *
    * @param array $config Logging configuration
    * @return self
    */
   protected function enableLogging(array $config = []): self
   {
      return $this->addMiddleware(new LoggingMiddleware($config));
   }

   /**
    * Execute the route with middleware pipeline
    */
   public function execute(): void
   {
      try {
         $response = $this->middleware->execute(self::request(), function (Request $request) {
            // Update the static request object in case middleware modified it
            self::setRequest($request);
            return $this->handle();
         });

         $response->send();
      } catch (ValidationException $e) {
         Response::validationError('Validation failed', $e->getErrors())->send();
      } catch (Exception $e) {
         self::handleException($e)->send();
      }
   }

   /**
    * Execute with timing information
    *
    * @return array Response and execution info
    */
   public function executeWithTiming(): array
   {
      try {
         return $this->middleware->executeWithTiming(self::request(), function (Request $request) {
            self::setRequest($request);
            return $this->handle();
         });
      } catch (ValidationException $e) {
         return [
            'response' => Response::validationError('Validation failed', $e->getErrors()),
            'execution_time' => 0,
            'memory_used' => 0,
            'middleware_count' => $this->middleware->count()
         ];
      } catch (Exception $e) {
         return [
            'response' => self::handleException($e),
            'execution_time' => 0,
            'memory_used' => 0,
            'middleware_count' => $this->middleware->count()
         ];
      }
   }

   /**
    * Get the middleware pipeline
    *
    * @return MiddlewarePipeline
    */
   public function getMiddleware(): MiddlewarePipeline
   {
      return $this->middleware;
   }

   /**
    * Get authenticated user from middleware
    *
    * @return mixed|null Authenticated user or null
    */
   protected static function getAuthenticatedUser()
   {
      return self::request()->route('authenticated_user');
   }

   /**
    * Check if user is authenticated via middleware
    *
    * @return bool
    */
   protected static function isAuthenticated(): bool
   {
      return self::getAuthenticatedUser() !== null;
   }

   /**
    * Get middleware execution order for debugging
    *
    * @return array Array of middleware names
    */
   public function getMiddlewareExecutionOrder(): array
   {
      return $this->middleware->getExecutionOrder();
   }

   /**
    * Check if specific middleware is registered
    *
    * @param string $middlewareClass Class name to check
    * @return bool
    */
   public function hasMiddleware(string $middlewareClass): bool
   {
      return $this->middleware->hasMiddleware($middlewareClass);
   }

   /**
    * Create a route with common API middleware
    *
    * @return self
    */
   protected function asApiRoute(): self
   {
      return $this->enableCors()
         ->enableRateLimit()
         ->enableLogging(['log_requests' => true, 'log_responses' => true]);
   }

   /**
    * Create a route with authentication and API middleware
    *
    * @param array $authConfig Authentication configuration
    * @return self
    */
   protected function asAuthenticatedApiRoute(array $authConfig = []): self
   {
      return $this->asApiRoute()
         ->requireAuth($authConfig);
   }

   /**
    * Create a route for admin-only access
    *
    * @return self
    */
   protected function asAdminRoute(): self
   {
      return $this->asAuthenticatedApiRoute()
         ->requireRoles(['admin']);
   }
}
