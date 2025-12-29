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

                    // Custom response format for login (tokens at root level for frontend compatibility)
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'access_token' => $result['access_token'],
                        'refresh_token' => $result['refresh_token'],
                        'user' => $result['user'],
                        'timestamp' => date('c')
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                } catch (Exception $e) {
                    Helpers::logError("Login failed for user {$payload['userid']}: " . $e->getMessage());
                    self::error('Invalid credentials', 401);
                }
            })(),

            // REFRESH TOKEN
            $method === 'POST' && $path === 'auth/refresh' => (function () {
                $payload = self::getPayload([
                    'refresh_token' => 'required'
                ]);

                try {
                    $result = Auth::refreshAccessToken($payload['refresh_token']);
                    self::success($result, 'Token refreshed', 200);
                } catch (Exception $e) {
                    Helpers::logError("Token refresh failed: " . $e->getMessage());
                    self::error('Invalid or expired refresh token', 401);
                }
            })(),

            // LOGOUT
            $method === 'POST' && $path === 'auth/logout' => (function () {
                $payload = self::getPayload([
                    'refresh_token' => 'required'
                ]);

                try {
                    Auth::logout($payload['refresh_token']);
                    self::success(null, 'Logged out successfully', 200);
                } catch (Exception $e) {
                    Helpers::logError("Logout failed: " . $e->getMessage());
                    self::error('Logout failed', 400);
                }
            })(),

            // FALLBACK
            default => self::error('Auth endpoint not found', 404),
        };
    }
}

// Dispatch the routes
AuthRoutes::handle();
