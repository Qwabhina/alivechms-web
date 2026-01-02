<?php

/**
 * BaseRoute with Dependency Injection - Enhanced Route Handler
 *
 * Enhanced version of BaseRoute that uses dependency injection for better
 * testability and reduced coupling. Provides the same functionality as
 * the original BaseRoute but with injected dependencies.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Application.php';

abstract class BaseRouteWithDI
{
   protected static ?array $decodedToken = null;
   protected static ?int   $currentUserId = null;

   // Injected dependencies
   protected static ?Container $container = null;
   protected static ?Auth $auth = null;
   protected static ?Validator $validator = null;
   protected static ?RateLimiter $rateLimiter = null;
   protected static ?ORM $orm = null;

   /**
    * Initialize dependencies from container
    */
   protected static function initializeDependencies(): void
   {
      if (self::$container === null) {
         self::$container = Application::resolve('Container');
         self::$auth = Application::resolve('Auth');
         self::$rateLimiter = Application::resolve('RateLimiter');
         self::$orm = Application::resolve('ORM');
      }
   }

   /**
    * Get route variables from global scope
    */
   protected static function getRouteVars(): array
   {
      global $method, $path, $pathParts;
      return [
         'method' => $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET',
         'path' => $path ?? '',
         'pathParts' => $pathParts ?? []
      ];
   }

   /**
    * Authenticate request using injected Auth service
    */
   protected static function authenticate(bool $required = true): bool
   {
      self::initializeDependencies();

      $token = self::$auth->getBearerToken();

      if (!$token) {
         if ($required) {
            Helpers::sendError('Unauthorized: Valid token required', 401);
         }
         return false;
      }

      $decoded = self::$auth->verify($token);

      if ($decoded === false) {
         if ($required) {
            Helpers::sendError('Unauthorized: Invalid or expired token', 401);
         }
         return false;
      }

      self::$decodedToken = $decoded;
      self::$currentUserId = (int)(self::$decodedToken['user_id'] ?? 0);

      return true;
   }

   /**
    * Check permissions using injected Auth service
    */
   protected static function authorize(string $permission): void
   {
      if (self::$currentUserId === null) {
         self::authenticate();
      }

      try {
         self::$auth->checkPermission($permission);
      } catch (Exception $e) {
         Helpers::logError("Authorization failed for user " . self::$currentUserId . ": " . $e->getMessage());
         self::error('Forbidden: Insufficient permissions', 403);
      }
   }

   /**
    * Get and validate JSON payload using injected Validator
    */
   protected static function getPayload(array $rules = [], bool $required = true): array
   {
      $payload = json_decode(file_get_contents('php://input'), true);

      if (!is_array($payload)) {
         if ($required) {
            Helpers::sendError('Invalid JSON payload', 400);
         }
         return [];
      }

      if (!empty($rules)) {
         self::initializeDependencies();
         $validator = new Validator($payload, $rules);
         if (!$validator->validate()) {
            Helpers::sendError('Validation failed', 422, $validator->errors());
         }
         return $validator->validated();
      }

      return $payload;
   }

   /**
    * Rate limiting using injected RateLimiter service
    */
   protected static function rateLimit(int $maxAttempts = 60, int $windowSeconds = 60): void
   {
      self::initializeDependencies();

      $identifier = Helpers::getClientIp();

      if (!self::$rateLimiter->check($identifier, $maxAttempts, $windowSeconds)) {
         $resetTime = self::$rateLimiter->getResetTime($identifier, $windowSeconds);
         $resetMinutes = ceil($resetTime / 60);

         http_response_code(429);
         echo json_encode([
            'status' => 'error',
            'message' => "Too many requests. Please try again in $resetMinutes minute(s).",
            'code' => 429,
            'retry_after' => $resetTime,
            'timestamp' => date('c')
         ]);
         exit;
      }
   }

   /**
    * Extract and validate ID from path
    */
   protected static function getIdFromPath(array $pathParts, int $position = 1): int
   {
      $id = (int)($pathParts[$position] ?? 0);
      if ($id <= 0) {
         Helpers::sendError('Invalid ID in path', 400);
      }
      return $id;
   }

   /**
    * Get pagination parameters
    */
   protected static function getPagination(): array
   {
      $page = max(1, (int)($_GET['page'] ?? 1));
      $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));

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
    * Send standard success response
    */
   protected static function success($data = null, string $message = 'Success', int $code = 200): never
   {
      http_response_code($code);
      echo json_encode([
         'status'   => 'success',
         'message'  => $message,
         'data'     => $data,
         'timestamp' => date('c')
      ], JSON_UNESCAPED_UNICODE);
      exit;
   }

   /**
    * Send paginated success response
    */
   protected static function paginated(array $data, int $total, int $page, int $limit): never
   {
      self::success([
         'data' => $data,
         'pagination' => [
            'page'   => $page,
            'limit'  => $limit,
            'total'  => $total,
            'pages'  => (int)ceil($total / $limit)
         ]
      ]);
   }

   /**
    * Send standard error response
    */
   protected static function error(string $message, int $code = 400, array $errors = []): never
   {
      http_response_code($code);

      $response = [
         'status'    => 'error',
         'message'   => $message,
         'code'      => $code,
         'timestamp' => date('c')
      ];

      if (!empty($errors)) {
         $response['errors'] = $errors;
      }

      echo json_encode($response, JSON_UNESCAPED_UNICODE);
      exit;
   }

   /**
    * Start database transaction using injected ORM
    */
   protected static function beginTransaction(): void
   {
      self::initializeDependencies();
      self::$orm->beginTransaction();
   }

   /**
    * Commit database transaction
    */
   protected static function commitTransaction(): void
   {
      self::initializeDependencies();
      self::$orm->commit();
   }

   /**
    * Rollback database transaction
    */
   protected static function rollbackTransaction(): void
   {
      self::initializeDependencies();
      self::$orm->rollBack();
   }
}
