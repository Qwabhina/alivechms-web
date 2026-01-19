<?php

/**
 * Authentication & Authorization Manager
 *
 * Handles JWT-based authentication, token generation, verification,
 * refresh tokens, permission checks, and secure logout.
 * 
 * Security improvements:
 * - Refresh tokens stored in HttpOnly cookies (XSS-proof)
 * - Access tokens returned to client (short-lived)
 * - CSRF protection for cookie-based auth
 *
 * @package  AliveChMS\Core
 * @version  1.1.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

require_once __DIR__ . '/Security/TokenManager.php';
require_once __DIR__ . '/PermissionCache.php';
require_once __DIR__ . '/RBAC.php';

class Auth
{
    private const ACCESS_TOKEN_TTL  = 1800;  // 30 minutes
    private const REFRESH_TOKEN_TTL = 86400; // 24 hours

    private static ?string $secretKey  = null;
    private static ?string $refreshSecretKey = null;

    /**
     * Initialize JWT secrets from environment variables
     *
     * @return void
     * @throws Exception If secrets are missing or empty
     */
    private static function initKeys(): void
    {
        if (self::$secretKey !== null) {
            return;
        }

        // Debug: Log environment loading
        Helpers::logError("[initKeys] Loading JWT secrets from environment");

        if (!isset($_ENV['JWT_SECRET']) || !isset($_ENV['JWT_REFRESH_SECRET'])) {
            Helpers::logError("[initKeys] JWT secrets not found in \$_ENV");
            throw new Exception('JWT secrets not configured. Ensure .env is loaded and contains JWT_SECRET and JWT_REFRESH_SECRET.');
        }

        if ($_ENV['JWT_SECRET'] === '' || $_ENV['JWT_REFRESH_SECRET'] === '') {
            throw new Exception('JWT secrets cannot be empty strings.');
        }

        self::$secretKey        = trim($_ENV['JWT_SECRET']);
        self::$refreshSecretKey = trim($_ENV['JWT_REFRESH_SECRET']);

        $secretPreview = substr(self::$secretKey, 0, 5) . '...';
        Helpers::logError("[initKeys] JWT_SECRET loaded: {$secretPreview}");
    }

    /**
     * Generate a JWT token
     *
     * @param array  $payload User payload
     * @param string $secret  Secret key to use
     * @param int    $ttl     Time-to-live in seconds
     * @return string Encoded JWT
     */
    private static function generateToken(array $user, string $secret, int $ttl): string
    {
        self::initKeys();

        $issuedAt  = time();
        $expireAt  = $issuedAt + $ttl;

        $payload = [
            'iat'      => $issuedAt,
            'exp'      => $expireAt,
            'user_id'  => $user['MbrID'],
            'username' => $user['Username'],
            'role'     => $user['Role'] ?? [],
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Generate access token (30 minutes)
     *
     * @param array $user User data (MbrID, Username, Role[])
     * @return string Access token
     */
    public static function generateAccessToken(array $user): string
    {
        self::initKeys();
        $token = self::generateToken($user, self::$secretKey, self::ACCESS_TOKEN_TTL);
        $secretPreview = substr(self::$secretKey, 0, 8) . '...';
        $tokenPreview = substr($token, 0, 30) . '...';
        Helpers::logError("[generateAccessToken] Generated token: {$tokenPreview} with secret: {$secretPreview}");
        return $token;
    }

    /**
     * Generate refresh token (24 hours)
     *
     * @param array $user User data (MbrID, Username)
     * @return string Refresh token
     */
    public static function generateRefreshToken(array $user): string
    {
        self::initKeys();
        return self::generateToken($user, self::$refreshSecretKey, self::REFRESH_TOKEN_TTL);
    }

    /**
     * Store refresh token in database
     *
     * @param int    $userId      Member ID
     * @param string $refreshToken Refresh token string
     * @return void
     */
    public static function storeRefreshToken(int $userId, string $token): void
    {
        $orm = new ORM();

        $decoded   = JWT::decode($token, new Key(self::$refreshSecretKey, 'HS256'));
        $expiresAt = date('Y-m-d H:i:s', $decoded->exp);

        $orm->insert('refresh_tokens', [
            'user_id'     => $userId,
            'token'       => $token,
            'expires_at'  => $expiresAt,
            'revoked'     => 0,
            'created_at'  => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verify and decode a JWT token
     *
     * @param string      $token  JWT string
     * @param string|null $secret Override secret (null = access secret)
     * @return array|false Decoded payload or false on failure
     */
    public static function verify(string $token, ?string $secret = null)
    {
        self::initKeys();
        $secret ??= self::$secretKey;

        // Debug: Log token info
        $tokenParts = explode('.', $token);
        $tokenPreview = substr($token, 0, 30) . '...';
        $secretPreview = substr($secret, 0, 8) . '...';
        $isRefreshSecret = ($secret === self::$refreshSecretKey);

        Helpers::logError("[verify] Token parts: " . count($tokenParts) . ", preview: {$tokenPreview}");
        Helpers::logError("[verify] Using " . ($isRefreshSecret ? "REFRESH" : "ACCESS") . " secret: {$secretPreview}");

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            Helpers::logError("[verify] Token verified successfully for user: " . ($decoded->user_id ?? 'unknown'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            Helpers::logError("[verify] Token expired: " . $e->getMessage());
        } catch (BeforeValidException | SignatureInvalidException $e) {
            Helpers::logError("[verify] Invalid token signature: " . $e->getMessage());
            // Log more details for debugging
            Helpers::logError("[verify] Token length: " . strlen($token) . ", Secret length: " . strlen($secret));
        } catch (Exception $e) {
            Helpers::logError("[verify] Token verification failed: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Extract Bearer token from Authorization header
     *
     * @return string|null Token or null if missing/invalid
     */
    public static function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if ($authHeader === null) {
            Helpers::logError('[getBearerToken] No Authorization header found');
            return null;
        }

        if (preg_match('/Bearer\s+([A-Za-z0-9._~+\/-]+=*)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $tokenPreview = substr($token, 0, 20) . '...';
            Helpers::logError("[getBearerToken] Extracted token: {$tokenPreview}");
            return $token;
        }

        Helpers::logError('[getBearerToken] Invalid Authorization header format: ' . $authHeader);
        return null;
    }

    /**
     * Get current authenticated user ID from token
     *
     * @return int User ID (MbrID)
     * @throws Exception If token is invalid or missing
     */
    public static function getCurrentUserId(): int
    {
        $token = self::getBearerToken();

        if (!$token) {
            throw new Exception('No authentication token provided');
        }

        $decoded = self::verify($token);

        if (!$decoded || !isset($decoded['user_id'])) {
            throw new Exception('Invalid or expired token');
        }

        return (int)$decoded['user_id'];
    }

    /**
     * Get branch ID of the currently authenticated user
     *
     * @return int BranchID of the Authenticated User
     * @throws Exception If user or branch not found
     */
    public static function getUserBranchId(): int
    {
        $userId = self::getCurrentUserId();
        $orm = new ORM();

        $user = $orm->getWhere('churchmember', ['MbrID' => $userId, 'Deleted' => 0]);

        if (empty($user)) {
            throw new Exception('User not found');
        }

        if (empty($user[0]['BranchID'])) {
            throw new Exception('User has no branch assigned');
        }

        return (int)$user[0]['BranchID'];
    }

    /**
     * Check if current user has required permission
     * Uses new RBAC system with caching and inheritance
     *
     * @param string $requiredPermission Permission name
     * @return void
     * @throws Exception On insufficient permission
     */
    public static function checkPermission(string $requiredPermission): void
    {
        $token = self::getBearerToken();

        if (!$token) {
            Helpers::sendFeedback('Unauthorized: No authentication token', 401);
        }

        $decoded = self::verify($token);

        if (!$decoded || !isset($decoded['user_id'])) {
            Helpers::sendFeedback('Unauthorized: Invalid or expired token', 401);
        }

        $userId = (int)$decoded['user_id'];

        // Use new RBAC system with caching and inheritance
        if (!RBAC::hasPermission($userId, $requiredPermission)) {
            Helpers::logError("Forbidden access attempt by user {$userId} for permission: $requiredPermission");
            Helpers::sendFeedback('Forbidden: Insufficient permissions', 403);
        }
    }

    /**
     * Get current user's permissions using new RBAC system
     * Returns actual permissions from database with inheritance
     *
     * @return array Array of permission names
     */
    public static function getCurrentUserPermissions(): array
    {
        $userId = self::getCurrentUserId();
        return RBAC::getUserPermissions($userId);
    }

    /**
     * Perform user login and issue authentication tokens
     *
     * @param string $username Username
     * @param string $password Plain-text password
     * @param bool $remember Remember me option (extends refresh token)
     * @param bool $useSecureCookies Store refresh token in HttpOnly cookie
     * @return array Tokens and user data
     */
    public static function login(string $username, string $password, bool $remember = false, bool $useSecureCookies = true): array
    {
        $orm = new ORM();

        $user = $orm->selectWithJoin(
            baseTable: 'userauthentication u',
            joins: [
                ['table' => 'churchmember c', 'on' => 'u.MbrID = c.MbrID'],
                ['table' => 'memberrole mr', 'on' => 'c.MbrID = mr.MbrID', 'type' => 'LEFT'],
                ['table' => 'churchrole cr', 'on' => 'mr.ChurchRoleID = cr.RoleID', 'type' => 'LEFT']
            ],
            fields: ['u.MbrID', 'u.Username', 'u.PasswordHash', 'c.*', 'cr.RoleName'],
            conditions: ['u.Username' => ':username', 'c.MbrMembershipStatus' => ':status'],
            params: [':username' => $username, ':status' => 'Active']
        )[0] ?? null;

        if (!$user || !password_verify($password, $user['PasswordHash'])) {
            Helpers::logError("Failed login attempt for username: $username");
            throw new Exception('Invalid credentials');
        }

        // Get all roles of user
        $roles = $orm->runQuery(
            "SELECT cr.RoleName FROM memberrole mr 
             JOIN churchrole cr ON mr.ChurchRoleID = cr.RoleID 
             WHERE mr.MbrID = :id",
            [':id' => $user['MbrID']]
        );

        $roleNames = array_column($roles, 'RoleName');

        $userData = [
            'MbrID'    => $user['MbrID'],
            'Username' => $user['Username'],
            'Role'     => $roleNames
        ];

        $refreshToken = self::generateRefreshToken($userData);
        self::storeRefreshToken($user['MbrID'], $refreshToken);

        // Store refresh token in HttpOnly cookie (secure)
        if ($useSecureCookies) {
            TokenManager::setRefreshTokenCookie($refreshToken, $remember);
        }

        // Generate CSRF token for cookie-based auth
        $csrfToken = TokenManager::generateCsrfToken();

        // Update last login
        $orm->update(
            'userauthentication',
            ['LastLoginAt' => date('Y-m-d H:i:s')],
            ['MbrID' => $user['MbrID']]
        );

        // Get actual permissions from database using new RBAC system
        $permissions = RBAC::getUserPermissions($user['MbrID']);

        unset($user['PasswordHash'], $user['CreatedAt'], $user['AuthUserID']);

        // Add permissions to user object
        $user['permissions'] = $permissions;

        $response = [
            'access_token'  => self::generateAccessToken($userData),
            'csrf_token'    => $csrfToken,
            'user'          => $user
        ];

        // Only include refresh token in response if NOT using secure cookies
        // This allows gradual migration - frontend can check for its presence
        if (!$useSecureCookies) {
            $response['refresh_token'] = $refreshToken;
        }

        return $response;
    }

    /**
     * Refresh access token using a valid refresh token
     * Supports both cookie-based and body-based refresh tokens
     *
     * @param string|null $refreshToken Valid refresh token (null = use cookie)
     * @return array New tokens
     */
    public static function refreshAccessToken(?string $refreshToken = null): array
    {
        // Ensure keys are initialized
        self::initKeys();

        // Try to get refresh token from cookie if not provided
        if (empty($refreshToken)) {
            $refreshToken = TokenManager::getRefreshTokenFromCookie();
            Helpers::logError("[refreshAccessToken] Got refresh token from cookie: " . ($refreshToken ? 'yes' : 'no'));
        }

        if (empty($refreshToken)) {
            Helpers::logError("[refreshAccessToken] No refresh token available");
            Helpers::sendError('Refresh token required');
        }

        $decoded = self::verify($refreshToken, self::$refreshSecretKey);

        if (!$decoded) {
            Helpers::logError("[refreshAccessToken] Refresh token verification failed");
            Helpers::sendError('Invalid or expired refresh token');
        }

        $orm = new ORM();
        $stored = $orm->getWhere('refresh_tokens', [
            'token'   => $refreshToken,
            'revoked' => 0
        ]);

        if (empty($stored)) {
            Helpers::sendError('Refresh token revoked or invalid');
        }

        $tokenRecord = $stored[0];

        if (strtotime($tokenRecord['expires_at']) < time()) {
            $orm->update('refresh_tokens', ['revoked' => 1], ['id' => $tokenRecord['id']]);
            Helpers::sendError('Refresh token expired');
        }

        // Revoke old refresh token
        $orm->update('refresh_tokens', ['revoked' => 1], ['id' => $tokenRecord['id']]);

        // Fetch fresh user roles
        $roles = $orm->runQuery(
            "SELECT cr.RoleName FROM memberrole mr 
             JOIN churchrole cr ON mr.ChurchRoleID = cr.RoleID 
             WHERE mr.MbrID = :id",
            [':id' => $decoded['user_id']]
        );

        $userData = [
            'MbrID'    => $decoded['user_id'],
            'Username' => $decoded['username'],
            'Role'     => array_column($roles, 'RoleName')
        ];

        $newRefreshToken = self::generateRefreshToken($userData);
        self::storeRefreshToken($userData['MbrID'], $newRefreshToken);

        // Update cookie with new refresh token
        TokenManager::setRefreshTokenCookie($newRefreshToken);

        // Generate new CSRF token
        $csrfToken = TokenManager::generateCsrfToken();

        return [
            'access_token'  => self::generateAccessToken($userData),
            'csrf_token'    => $csrfToken
            // refresh_token not returned - it's in the HttpOnly cookie
        ];
    }

    /**
     * Logout – revoke refresh token and clear cookie
     *
     * @param string|null $refreshToken Token to revoke (null = use cookie)
     * @return void
     */
    public static function logout(?string $refreshToken = null): void
    {
        // Try to get refresh token from cookie if not provided
        if (empty($refreshToken)) {
            $refreshToken = TokenManager::getRefreshTokenFromCookie();
        }

        if (!empty($refreshToken)) {
            $orm = new ORM();
            $orm->update('refresh_tokens', ['revoked' => 1], ['token' => $refreshToken]);
        }

        // Clear the HttpOnly cookie
        TokenManager::clearRefreshTokenCookie();
    }

    /**
     * Clean up expired tokens (run via cron)
     */
    public static function cleanupExpiredTokens(): int
    {
        $orm = new ORM();

        // Delete tokens expired more than 7 days ago
        $result = $orm->runQuery(
            "DELETE FROM refresh_tokens 
             WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)",
            []
        );

        return $result ? count($result) : 0;
    }
}