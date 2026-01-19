<?php

/**
 * Authentication API Routes – v1
 *
 * Handles login, token refresh, and logout.
 * Public endpoints — no token required.
 * 
 * Security: Refresh tokens stored in HttpOnly cookies
 *
 * @package  AliveChMS\Routes
 * @version  1.1.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ResponseHelper.php';
require_once __DIR__ . '/../core/Security/TokenManager.php';

class AuthRoutes extends BaseRoute
{
    public static function handle(): void
    {
        // Get route variables from global scope
        global $method, $path;

        // Rate limit to prevent brute-force (IP-based)
        self::rateLimit(maxAttempts: 20, windowSeconds: 300);

        // No required auth for public endpoints
        self::authenticate(false);

        match (true) {
            // LOGIN
            $method === 'POST' && $path === 'auth/login' => (function () {
                $payload = self::getPayload([
                    'userid' => 'required|max:50',
                    'passkey' => 'required',
                    'remember' => 'nullable'
                ]);

                try {
                    $remember = filter_var($payload['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    Helpers::logError("[auth/login] Attempting login for user: " . $payload['userid']);

                    $result = Auth::login($payload['userid'], $payload['passkey'], $remember);

                    // Clear rate limit on successful login
                    RateLimiter::clear(Helpers::getClientIp());

                    Helpers::logError("[auth/login] Login successful, access_token length: " . strlen($result['access_token']));
                    ResponseHelper::success($result, 'Login successful');
                } catch (Exception $e) {
                    Helpers::logError("Login failed for user {$payload['userid']}: " . $e->getMessage());
                    ResponseHelper::unauthorized('Invalid credentials');
                }
            })(),

            // REFRESH TOKEN - Now supports cookie-based refresh
            $method === 'POST' && $path === 'auth/refresh' => (function () {
                // refresh_token is optional in body - will use cookie if not provided
                $payload = self::getPayload([], false);

                try {
                    $refreshToken = $payload['refresh_token'] ?? null;
                    Helpers::logError("[auth/refresh] Refresh token from body: " . ($refreshToken ? 'yes' : 'no'));

                    // Check cookie
                    $cookieToken = TokenManager::getRefreshTokenFromCookie();
                    Helpers::logError("[auth/refresh] Refresh token from cookie: " . ($cookieToken ? 'yes' : 'no'));

                    $result = Auth::refreshAccessToken($refreshToken);
                    ResponseHelper::success($result, 'Token refreshed');
                } catch (Exception $e) {
                    Helpers::logError("Token refresh failed: " . $e->getMessage());
                    ResponseHelper::unauthorized('Invalid or expired refresh token');
                }
            })(),

            // LOGOUT - Now supports cookie-based logout
            $method === 'POST' && $path === 'auth/logout' => (function () {
                $payload = self::getPayload([], false);

                try {
                    $refreshToken = $payload['refresh_token'] ?? null;
                    Auth::logout($refreshToken);
                    ResponseHelper::success(null, 'Logged out successfully');
                } catch (Exception $e) {
                    Helpers::logError("Logout failed: " . $e->getMessage());
                    ResponseHelper::error('Logout failed', 400);
                }
            })(),

            // GET CSRF TOKEN - For frontend to get fresh CSRF token
            $method === 'GET' && $path === 'auth/csrf' => (function () {
                $csrfToken = TokenManager::generateCsrfToken();
                ResponseHelper::success([
                    'csrf_token' => $csrfToken,
                    'config' => TokenManager::getClientConfig()
                ], 'CSRF token generated');
            })(),

            // CHECK AUTH STATUS - Verify if current session is valid
            $method === 'GET' && $path === 'auth/status' => (function () {
                $token = Auth::getBearerToken();

                if (!$token) {
                    ResponseHelper::success(['authenticated' => false], 'Not authenticated');
                }

                $decoded = Auth::verify($token);

                if (!$decoded) {
                    ResponseHelper::success(['authenticated' => false], 'Token invalid or expired');
                }

                ResponseHelper::success([
                    'authenticated' => true,
                    'user_id' => $decoded['user_id'],
                    'username' => $decoded['username']
                ], 'Authenticated');
            })(),

            // FALLBACK
            default => ResponseHelper::notFound('Auth endpoint not found'),
        };
    }
}

// Dispatch the routes
AuthRoutes::handle();
