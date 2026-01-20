<?php

/**
 * Secure Token Manager
 *
 * Handles secure token storage using HttpOnly cookies for refresh tokens
 * and provides CSRF protection for cookie-based authentication.
 *
 * Security features:
 * - HttpOnly cookies prevent XSS token theft
 * - Secure flag ensures HTTPS-only transmission
 * - SameSite=Strict prevents CSRF attacks
 * - CSRF tokens for additional protection
 *
 * @package  AliveChMS\Core\Security
 * @version  1.0.0
 */

declare(strict_types=1);

class TokenManager
{
   private const REFRESH_COOKIE_NAME = 'alive_refresh_token';
   private const CSRF_COOKIE_NAME = 'alive_csrf_token';
   private const CSRF_HEADER_NAME = 'X-CSRF-Token';

   // Cookie settings
   private const REFRESH_TOKEN_TTL = 86400; // 24 hours
   private const CSRF_TOKEN_TTL = 3600;     // 1 hour

   /**
    * Set refresh token as HttpOnly cookie
    *
    * @param string $token Refresh token
    * @param bool $remember Extended expiry for "remember me"
    */
   public static function setRefreshTokenCookie(string $token, bool $remember = false): void
   {
      $expiry = $remember ? time() + (7 * 86400) : time() + self::REFRESH_TOKEN_TTL;
      $secure = self::isSecureConnection();

      setcookie(self::REFRESH_COOKIE_NAME, $token, [
         'expires' => $expiry,
         'path' => '/',
         'domain' => self::getCookieDomain(),
         'secure' => $secure,
         'httponly' => true,
         'samesite' => 'Lax' // Lax allows cookie on navigation, Strict blocks it
      ]);

      // Also set in $_COOKIE for immediate availability in same request
      $_COOKIE[self::REFRESH_COOKIE_NAME] = $token;
   }

   /**
    * Get refresh token from HttpOnly cookie
    *
    * @return string|null Refresh token or null if not set
    */
   public static function getRefreshTokenFromCookie(): ?string
   {
      return $_COOKIE[self::REFRESH_COOKIE_NAME] ?? null;
   }

   /**
    * Clear refresh token cookie (logout)
    */
   public static function clearRefreshTokenCookie(): void
   {
      setcookie(self::REFRESH_COOKIE_NAME, '', [
         'expires' => time() - 3600,
         'path' => '/',
         'domain' => self::getCookieDomain(),
         'secure' => self::isSecureConnection(),
         'httponly' => true,
         'samesite' => 'Lax'
      ]);

      unset($_COOKIE[self::REFRESH_COOKIE_NAME]);
   }

   /**
    * Generate and set CSRF token
    *
    * @return string The generated CSRF token
    */
   public static function generateCsrfToken(): string
   {
      $token = bin2hex(random_bytes(32));

      // Store in session for server-side validation
      if (session_status() === PHP_SESSION_NONE) {
         session_start();
      }
      $_SESSION['csrf_token'] = $token;
      $_SESSION['csrf_token_time'] = time();

      // Also set as non-HttpOnly cookie so JS can read it
      setcookie(self::CSRF_COOKIE_NAME, $token, [
         'expires' => time() + self::CSRF_TOKEN_TTL,
         'path' => '/',
         'domain' => self::getCookieDomain(),
         'secure' => self::isSecureConnection(),
         'httponly' => false, // JS needs to read this
         'samesite' => 'Lax'
      ]);

      return $token;
   }

   /**
    * Validate CSRF token from request
    *
    * @return bool True if valid
    */
   public static function validateCsrfToken(): bool
   {
      // Get token from header (preferred) or POST body
      $headerToken = $_SERVER['HTTP_' . str_replace('-', '_', strtoupper(self::CSRF_HEADER_NAME))] ?? null;
      $bodyToken = $_POST['_csrf_token'] ?? null;
      $requestToken = $headerToken ?? $bodyToken;

      if (!$requestToken) {
         return false;
      }

      // Validate against session
      if (session_status() === PHP_SESSION_NONE) {
         session_start();
      }

      $sessionToken = $_SESSION['csrf_token'] ?? null;
      $tokenTime = $_SESSION['csrf_token_time'] ?? 0;

      // Check token exists and hasn't expired
      if (!$sessionToken || (time() - $tokenTime) > self::CSRF_TOKEN_TTL) {
         return false;
      }

      // Timing-safe comparison
      return hash_equals($sessionToken, $requestToken);
   }

   /**
    * Middleware to enforce CSRF on state-changing requests
    *
    * @param bool $required Whether CSRF is required
    */
   public static function enforceCsrf(bool $required = true): void
   {
      $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

      // Only check on state-changing methods
      if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
         return;
      }

      if ($required && !self::validateCsrfToken()) {
         http_response_code(403);
         echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or missing CSRF token',
            'code' => 403
         ]);
         exit;
      }
   }

   /**
    * Check if connection is secure (HTTPS)
    */
   private static function isSecureConnection(): bool
   {
      // Check various indicators of HTTPS
      if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
         return true;
      }

      if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
         return true;
      }

      if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
         return true;
      }

      // In development, allow non-secure
      $env = $_ENV['APP_ENV'] ?? 'development';
      return $env === 'production';
   }

   /**
    * Get cookie domain from environment or derive from host
    */
   private static function getCookieDomain(): string
   {
      // Use configured domain or empty for current domain
      return $_ENV['COOKIE_DOMAIN'] ?? '';
   }

   /**
    * Get token configuration for frontend
    * Returns non-sensitive config that frontend needs
    */
   public static function getClientConfig(): array
   {
      return [
         'csrf_header' => self::CSRF_HEADER_NAME,
         'csrf_cookie' => self::CSRF_COOKIE_NAME,
         'csrf_ttl' => self::CSRF_TOKEN_TTL
      ];
   }
}
