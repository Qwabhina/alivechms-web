<?php

/**
 * Base HTTP Route with Request/Response Objects
 *
 * Enhanced route handler that uses Request/Response wrapper classes
 * for better testability and cleaner HTTP handling.
 *
 * @package  AliveChMS\Core\Http
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/../Application.php';

abstract class BaseHttpRoute
{
   protected static ?Request $request = null;
   protected static ?array $decodedToken = null;
   protected static ?int $currentUserId = null;

   /**
    * Initialize request object
    */
   protected static function initializeRequest(): void
   {
      if (self::$request === null) {
         self::$request = Request::createFromGlobals();
      }
   }

   /**
    * Get request object
    */
   protected static function request(): Request
   {
      self::initializeRequest();
      return self::$request;
   }

   /**
    * Set request object (for testing)
    */
   protected static function setRequest(Request $request): void
   {
      self::$request = $request;
   }

   /**
    * Authenticate request using injected Auth service
    */
   protected static function authenticate(bool $required = true): bool
   {
      $request = self::request();
      $token = $request->bearerToken();

      if (!$token) {
         if ($required) {
            Response::unauthorized('Valid token required')->sendAndExit();
         }
         return false;
      }

      $auth = Application::resolve('Auth');
      $decoded = $auth->verify($token);

      if ($decoded === false) {
         if ($required) {
            Response::unauthorized('Invalid or expired token')->sendAndExit();
         }
         return false;
      }

      self::$decodedToken = $decoded;
      self::$currentUserId = (int)(self::$decodedToken['user_id'] ?? 0);

      return true;
   }

   /**
    * Check permissions
    */
   protected static function authorize(string $permission): void
   {
      if (self::$currentUserId === null) {
         self::authenticate();
      }

      try {
         $auth = Application::resolve('Auth');
         $auth->checkPermission($permission);
      } catch (Exception $e) {
         Helpers::logError("Authorization failed for user " . self::$currentUserId . ": " . $e->getMessage());
         Response::forbidden('Insufficient permissions')->sendAndExit();
      }
   }

   /**
    * Validate request input
    */
   protected static function validate(array $rules): array
   {
      $request = self::request();

      try {
         return $request->validate($rules);
      } catch (ValidationException $e) {
         Response::validationError('Validation failed', $e->getErrors())->sendAndExit();
      }
   }

   /**
    * Rate limiting
    */
   protected static function rateLimit(int $maxAttempts = 60, int $windowSeconds = 60): void
   {
      $request = self::request();
      $rateLimiter = Application::resolve('RateLimiter');

      $identifier = $request->ip();

      if (!$rateLimiter->check($identifier, $maxAttempts, $windowSeconds)) {
         $resetTime = $rateLimiter->getResetTime($identifier, $windowSeconds);
         $resetMinutes = ceil($resetTime / 60);

         Response::rateLimited(
            "Too many requests. Please try again in $resetMinutes minute(s).",
            $resetTime
         )->sendAndExit();
      }
   }

   /**
    * Get route parameter
    */
   protected static function getRouteParam(string $key, $default = null)
   {
      return self::request()->route($key, $default);
   }

   /**
    * Get ID from route (common pattern)
    */
   protected static function getIdFromRoute(string $key = 'id'): int
   {
      $id = (int)self::getRouteParam($key, 0);
      if ($id <= 0) {
         Response::error('Invalid ID in route', 400)->sendAndExit();
      }
      return $id;
   }

   /**
    * Get pagination parameters
    */
   protected static function getPagination(): array
   {
      $request = self::request();
      $page = max(1, (int)$request->query('page', 1));
      $limit = min(100, max(1, (int)$request->query('limit', 10)));

      return ['page' => $page, 'limit' => $limit];
   }

   /**
    * Get current user ID
    */
   protected static function getCurrentUserId(): ?int
   {
      return self::$currentUserId;
   }

   /**
    * Start database transaction
    */
   protected static function beginTransaction(): void
   {
      $orm = Application::resolve('ORM');
      $orm->beginTransaction();
   }

   /**
    * Commit database transaction
    */
   protected static function commitTransaction(): void
   {
      $orm = Application::resolve('ORM');
      $orm->commit();
   }

   /**
    * Rollback database transaction
    */
   protected static function rollbackTransaction(): void
   {
      $orm = Application::resolve('ORM');
      $orm->rollBack();
   }

   /**
    * Handle exceptions and return appropriate response
    */
   protected static function handleException(Exception $e): Response
   {
      Helpers::logError("Route exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

      return Response::fromException($e);
   }

   /**
    * Execute route with exception handling
    */
   protected static function executeWithExceptionHandling(callable $callback): void
   {
      try {
         $callback();
      } catch (Exception $e) {
         self::handleException($e)->sendAndExit();
      }
   }

   /**
    * Common response helpers
    */
   protected static function success($data = null, string $message = 'Success', int $statusCode = 200): Response
   {
      return Response::success($data, $message, $statusCode);
   }

   protected static function error(string $message, int $statusCode = 400, array $errors = []): Response
   {
      return Response::error($message, $statusCode, $errors);
   }

   protected static function paginated(array $data, int $total, int $page, int $limit): Response
   {
      return Response::paginated($data, $total, $page, $limit);
   }

   protected static function notFound(string $message = 'Not Found'): Response
   {
      return Response::notFound($message);
   }

   protected static function created($data, string $message = 'Created'): Response
   {
      return Response::success($data, $message, 201);
   }

   protected static function noContent(): Response
   {
      return Response::make('', 204);
   }
}
