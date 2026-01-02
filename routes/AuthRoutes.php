<?php

/**
 * Authentication API Routes – v1
 *
 * Handles login, token refresh, and logout.
 * Public endpoints — no token required.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ResponseHelper.php';
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
                    'passkey' => 'required'
                ]);

                try {
                    $result = Auth::login($payload['userid'], $payload['passkey']);
                    // Clear rate limit on successful login
                    RateLimiter::clear(Helpers::getClientIp());

                    // FIXED: Use standard response format (consistent with other endpoints)
                    ResponseHelper::success($result, 'Login successful');
                } catch (Exception $e) {
                    Helpers::logError("Login failed for user {$payload['userid']}: " . $e->getMessage());
                    ResponseHelper::unauthorized('Invalid credentials');
                }
            })(),

            // REFRESH TOKEN
            $method === 'POST' && $path === 'auth/refresh' => (function () {
                $payload = self::getPayload([
                    'refresh_token' => 'required'
                ]);

                try {
                    $result = Auth::refreshAccessToken($payload['refresh_token']);
                    ResponseHelper::success($result, 'Token refreshed');
                } catch (Exception $e) {
                    Helpers::logError("Token refresh failed: " . $e->getMessage());
                    ResponseHelper::unauthorized('Invalid or expired refresh token');
                }
            })(),

            // LOGOUT
            $method === 'POST' && $path === 'auth/logout' => (function () {
                $payload = self::getPayload([
                    'refresh_token' => 'required'
                ]);

                try {
                    Auth::logout($payload['refresh_token']);
                    ResponseHelper::success(null, 'Logged out successfully');
                } catch (Exception $e) {
                    Helpers::logError("Logout failed: " . $e->getMessage());
                    ResponseHelper::error('Logout failed', 400);
                }
            })(),

            // FALLBACK
            default => ResponseHelper::notFound('Auth endpoint not found'),
        };
    }
}

// Dispatch the routes
AuthRoutes::handle();
