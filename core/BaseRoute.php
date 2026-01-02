<?php

/**
 * BaseRoute - Centralised Route Handler
 *
 * Eliminates duplication across all route files by providing:
 * - Unified authentication & token decoding
 * - Permission checking
 * - Standardised JSON payload parsing & validation
 * - Path-based ID extraction with validation
 * - Pagination & filter helpers
 * - Rate limiting integration
 * - Transaction support
 * - Consistent success/error responses
 *
 * All route classes should extend this class.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

if (!class_exists('Auth')) {
   require_once __DIR__ . '/Auth.php';
}
if (!class_exists('RateLimiter')) {
   require_once __DIR__ . '/RateLimiter.php';
}
if (!class_exists('Validator')) {
   require_once __DIR__ . '/Validator.php';
}
if (!class_exists('Helpers')) {
   require_once __DIR__ . '/Helpers.php';
}
if (!class_exists('ResponseHelper')) {
   require_once __DIR__ . '/ResponseHelper.php';
}
if (!class_exists('ORM')) {
   require_once __DIR__ . '/ORM.php';
}

if (!class_exists('Database')) {
   require_once __DIR__ . '/Database.php';
}
if (!class_exists('Cache')) {
   require_once __DIR__ . '/Cache.php';
}

abstract class BaseRoute
{
   protected static ?array $decodedToken = null;
   protected static ?int   $currentUserId = null;

   /**
    * Get route variables from global scope
    * Ensures consistency across all route files
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
    * Authenticate request and decode JWT token
    *
    * @param bool $required Whether authentication is mandatory (default: true)
    * @return bool True if authenticated (or not required)
    */
   protected static function authenticate(bool $required = true): bool
   {
      $token = Auth::getBearerToken();

      if (!$token) {
         if ($required) {
            ResponseHelper::unauthorized('Valid token required');
         }
         return false;
      }

      $decoded = Auth::verify($token);

      if ($decoded === false) {
         if ($required) {
            ResponseHelper::unauthorized('Invalid or expired token');
         }
         return false;
      }

      self::$decodedToken = $decoded;
      self::$currentUserId = (int)(self::$decodedToken['user_id'] ?? 0);

      return true;
   }

   /**
    * Check if current user has required permission
    *
    * @param string $permission Permission name (e.g., 'manage_members')
    * @return void Exits with 403 if not authorized
    */
   protected static function authorize(string $permission): void
   {
      if (self::$currentUserId === null) {
         self::authenticate();
      }

      try {
         Auth::checkPermission($permission);
      } catch (Exception $e) {
         Helpers::logError("Authorization failed for user " . self::$currentUserId . ": " . $e->getMessage());
         ResponseHelper::forbidden('Insufficient permissions');
      }
   }

   /**
    * Get and validate JSON payload from request body
    *
    * @param array $rules    Validation rules (passed to Helpers::validateInput)
    * @param bool  $required Whether payload is required
    * @return array          Validated and sanitized payload
    */
   protected static function getPayload(array $rules = [], bool $required = true): array
   {
      $payload = json_decode(file_get_contents('php://input'), true);

      if (!is_array($payload)) {
         if ($required) {
            ResponseHelper::error('Invalid JSON payload', 400);
         }
         return [];
      }

      if (!empty($rules)) {
         $validator = Validator::make($payload, $rules);
         if ($validator->fails()) {
            ResponseHelper::validationError($validator->errors());
         }
         return $validator->validated();
      }

      return $payload;
   }

   /**
    * Extract and validate numeric ID from path parts
    *
    * @param array  $pathParts Path segments from routing
    * @param int    $position  Array index of the ID
    * @param string $name      Name for error message (e.g., 'Member ID')
    * @return int              Validated ID
    */
   protected static function getIdFromPath(array $pathParts, int $position, string $name = 'ID'): int
   {
      if (!isset($pathParts[$position]) || !is_numeric($pathParts[$position])) {
         ResponseHelper::error("Valid {$name} required", 400);
      }

      return (int)$pathParts[$position];
   }

   /**
    * Get pagination parameters from query string
    *
    * @param int $defaultLimit Default items per page
    * @param int $maxLimit     Maximum allowed limit (prevents abuse)
    * @return array [page: int, limit: int, offset: int]
    */
   protected static function getPagination(int $defaultLimit = 10, int $maxLimit = 100): array
   {
      $page  = max(1, (int)($_GET['page'] ?? 1));
      $limit = max(1, min($maxLimit, (int)($_GET['limit'] ?? $defaultLimit)));
      $offset = ($page - 1) * $limit;

      return [$page, $limit, $offset];
   }

   /**
    * Extract allowed filters from query string with sanitization
    *
    * @param array $allowedFilters List of permitted filter keys
    * @return array Sanitized filter values
    */
   protected static function getFilters(array $allowedFilters): array
   {
      $filters = [];

      foreach ($allowedFilters as $key) {
         if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $filters[$key] = Helpers::sanitize($_GET[$key]);
         }
      }

      return $filters;
   }

   /**
    * Apply rate limiting to the current request
    *
    * @param string $identifier     Custom identifier (defaults to IP)
    * @param int    $maxAttempts    Max requests allowed
    * @param int    $windowSeconds  Time window in seconds
    * @return void                  Exits with 429 if limited
    */
   protected static function rateLimit(
      string $identifier = '',
      int $maxAttempts = 60,
      int $windowSeconds = 60
   ): void {
      if ($identifier === '') {
         $identifier = Helpers::getClientIp();
      }

      RateLimiter::enforce($identifier, $maxAttempts, $windowSeconds);
   }

   /**
    * Execute database operation within a transaction
    *
    * @param callable $operation Callback receiving ORM instance
    * @return mixed Result from operation
    */
   protected static function withTransaction(callable $operation)
   {
      $orm = new ORM();

      try {
         $orm->beginTransaction();
         $result = $operation($orm);
         $orm->commit();
         return $result;
      } catch (Exception $e) {
         if ($orm->inTransaction()) {
            $orm->rollback();
         }
         Helpers::logError('Transaction failed: ' . $e->getMessage());
         throw $e;
      }
   }

   /**
    * Get current authenticated user ID
    *
    * @return int|null User ID or null if not authenticated
    */
   protected static function getCurrentUserId(): ?int
   {
      return self::$currentUserId;
   }

   /**
    * Send standard success response
    *
    * @param mixed  $data    Response data
    * @param string $message Optional message
    * @param int    $code    HTTP status code
    * @return never
    */
   protected static function success($data = null, string $message = 'Success', int $code = 200): never
   {
      ResponseHelper::success($data, $message, $code);
   }

   /**
    * Send standard paginated success response
    *
    * @param array $data  Result rows
    * @param int   $total Total records
    * @param int   $page  Current page
    * @param int   $limit Items per page
    * @return never
    */
   protected static function paginated(array $data, int $total, int $page, int $limit): never
   {
      ResponseHelper::paginated($data, $total, $page, $limit);
   }

   /**
    * Send standard error response
    *
    * @param string $message Error message
    * @param int    $code    HTTP status code
    * @param array  $errors  Additional error details (optional)
    * @return never
    */
   protected static function error(string $message, int $code = 400, array $errors = []): never
   {
      ResponseHelper::error($message, $code, $errors);
   }
}
