<?php

/**
 * Rate Limit Middleware
 *
 * Implements rate limiting for API endpoints using the existing RateLimiter.
 * Supports both IP-based and user-based rate limiting with different limits
 * for authenticated vs anonymous users.
 *
 * @package  AliveChMS\Core\Http\Middleware
 * @version  2.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../../RateLimiter.php';
require_once __DIR__ . '/../../Auth.php';

class RateLimitMiddleware extends Middleware
{
   private int $maxAttempts;
   private int $windowSeconds;
   private string $keyPrefix;
   private bool $userBased;
   private int $authenticatedLimit;
   private int $anonymousLimit;

   public function __construct(
      $rateLimiter = null, // Kept for compatibility but not used
      int $maxAttempts = 60,
      int $decayMinutes = 1,
      string $keyPrefix = 'api',
      bool $userBased = false,
      int $authenticatedLimit = null,
      int $anonymousLimit = null
   ) {
      $this->maxAttempts = $maxAttempts;
      $this->windowSeconds = $decayMinutes * 60;
      $this->keyPrefix = $keyPrefix;
      $this->userBased = $userBased;
      $this->authenticatedLimit = $authenticatedLimit ?? $maxAttempts * 2; // Higher limit for authenticated users
      $this->anonymousLimit = $anonymousLimit ?? $maxAttempts;
   }

   public function handle(Request $request, callable $next): Response
   {
      $key = $this->resolveRequestSignature($request);
      $limit = $this->getApplicableLimit($request);

      if (!RateLimiter::check($key, $limit, $this->windowSeconds)) {
         return $this->buildRateLimitResponse($key, $limit);
      }

      $response = $next($request);

      return $this->addRateLimitHeaders($response, $key, $limit);
   }

   public function getPriority(): int
   {
      return 20; // Execute early, but after CORS
   }

   /**
    * Resolve request signature for rate limiting
    */
   private function resolveRequestSignature(Request $request): string
   {
      if ($this->userBased) {
         // Try to get user ID from request
         $userId = $this->getUserId($request);

         if ($userId) {
            return $this->keyPrefix . ':user:' . $userId;
         }
      }

      // Fall back to IP-based limiting
      $ip = $request->ip();
      $route = $request->getPath();

      return $this->keyPrefix . ':ip:' . sha1($ip . '|' . $route);
   }

   /**
    * Get user ID from request
    */
   private function getUserId(Request $request): ?int
   {
      // Try to get from Authorization header
      $authHeader = $request->getHeader('Authorization');

      if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
         $token = substr($authHeader, 7);
         $decoded = Auth::verify($token);

         if ($decoded && isset($decoded['user_id'])) {
            return (int)$decoded['user_id'];
         }
      }

      // Try to get from session (if available)
      if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
         return (int)$_SESSION['user_id'];
      }

      return null;
   }

   /**
    * Get applicable rate limit based on authentication status
    */
   private function getApplicableLimit(Request $request): int
   {
      if (!$this->userBased) {
         return $this->maxAttempts;
      }

      $userId = $this->getUserId($request);

      return $userId ? $this->authenticatedLimit : $this->anonymousLimit;
   }

   /**
    * Build rate limit exceeded response
    */
   private function buildRateLimitResponse(string $key, int $limit): Response
   {
      $retryAfter = RateLimiter::getResetTime($key, $this->windowSeconds);

      return Response::rateLimited(
         'Too many requests. Please try again later.',
         $retryAfter
      );
   }

   /**
    * Add rate limit headers to response
    */
   private function addRateLimitHeaders(Response $response, string $key, int $limit): Response
   {
      $remaining = RateLimiter::getRemaining($key, $limit, $this->windowSeconds);
      $retryAfter = RateLimiter::getResetTime($key, $this->windowSeconds);

      return $response->withHeaders([
         'X-RateLimit-Limit' => (string)$limit,
         'X-RateLimit-Remaining' => (string)$remaining,
         'X-RateLimit-Reset' => (string)(time() + $retryAfter)
      ]);
   }

   /**
    * Create IP-based rate limiting middleware
    */
   public static function forIp(int $maxAttempts = 60, int $decayMinutes = 1): self
   {
      return new self(null, $maxAttempts, $decayMinutes, 'api', false);
   }

   /**
    * Create user-based rate limiting middleware
    */
   public static function forUser(
      int $authenticatedLimit = 120,
      int $anonymousLimit = 60,
      int $decayMinutes = 1
   ): self {
      return new self(
         null,
         $anonymousLimit,
         $decayMinutes,
         'api',
         true,
         $authenticatedLimit,
         $anonymousLimit
      );
   }

   /**
    * Create role-based rate limiting middleware
    */
   public static function forRole(array $roleLimits, int $defaultLimit = 60, int $decayMinutes = 1): self
   {
      // This would require extending the middleware to support role-based limits
      // For now, return user-based middleware
      return self::forUser($defaultLimit * 2, $defaultLimit, $decayMinutes);
   }
}
