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
        // $secretPreview = substr(self::$secretKey, 0, 8) . '...';
        $tokenPreview = substr($token, 0, 30) . '...';
        // Helpers::logError("[generateAccessToken] Generated token: {$tokenPreview} with secret: {$secretPreview}");
        Helpers::logError("[generateAccessToken] Generated token: {$tokenPreview}");
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
     * @param int    $userId      User ID (from user_authentication table)
     * @param string $refreshToken Refresh token string
     * @return void
     */
    public static function storeRefreshToken(int $userId, string $token): void
    {
        $orm = new ORM();

        $decoded   = JWT::decode($token, new Key(self::$refreshSecretKey, 'HS256'));
        $expiresAt = date('Y-m-d H:i:s', $decoded->exp);

        // NEW: Store in user_sessions table with device info
        $orm->insert('user_sessions', [
            'UserID'      => $userId,
            'TokenHash'   => hash('sha256', $token), // Store hash for security
            'DeviceInfo'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'IPAddress'   => $_SERVER['REMOTE_ADDR'] ?? null,
            'UserAgent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ExpiresAt'   => $expiresAt,
            'IsRevoked'   => 0,
            'CreatedAt'   => date('Y-m-d H:i:s')
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

        // Helpers::logError("[verify] Token parts: " . count($tokenParts) . ", preview: {$tokenPreview}");
        // Helpers::logError("[verify] Using " . ($isRefreshSecret ? "REFRESH" : "ACCESS") . " secret: {$secretPreview}");

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            // Helpers::logError("[verify] Token verified successfully for user: " . ($decoded->user_id ?? 'unknown'));
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

        // NEW: Updated table names and membership status check
        $user = $orm->selectWithJoin(
            baseTable: 'user_authentication u',
            joins: [
                ['table' => 'churchmember c', 'on' => 'u.MbrID = c.MbrID'],
                ['table' => 'membership_status ms', 'on' => 'c.MbrMembershipStatusID = ms.StatusID'],
                ['table' => 'member_role mr', 'on' => 'c.MbrID = mr.MbrID AND mr.IsActive = 1', 'type' => 'LEFT'],
                ['table' => 'church_role cr', 'on' => 'mr.RoleID = cr.RoleID', 'type' => 'LEFT']
            ],
            fields: ['u.UserID', 'u.MbrID', 'u.Username', 'u.PasswordHash', 'u.IsLocked', 'u.FailedLoginAttempts', 'u.EmailVerified', 'c.*', 'ms.StatusName as MembershipStatus', 'cr.RoleName'],
            conditions: ['u.Username' => ':username', 'c.Deleted' => 0],
            params: [':username' => $username]
        )[0] ?? null;

        if (!$user) {
            Helpers::logError("Failed login attempt for username: $username - User not found");
            throw new Exception('Invalid credentials');
        }

        // NEW: Check if account is locked
        if ($user['IsLocked']) {
            Helpers::logError("Login attempt for locked account: $username");
            throw new Exception('Account is locked. Please contact administrator.');
        }

        // NEW: Check if membership is active
        if ($user['MembershipStatus'] !== 'Active') {
            Helpers::logError("Login attempt for inactive member: $username (Status: {$user['MembershipStatus']})");
            throw new Exception('Your membership is not active. Please contact administrator.');
        }

        // Verify password
        if (!password_verify($password, $user['PasswordHash'])) {
            // NEW: Increment failed attempts and lock if needed
            $failedAttempts = ($user['FailedLoginAttempts'] ?? 0) + 1;
            $isLocked = $failedAttempts >= 5 ? 1 : 0;

            $orm->update('user_authentication', [
                'FailedLoginAttempts' => $failedAttempts,
                'IsLocked' => $isLocked
            ], ['UserID' => $user['UserID']]);

            if ($isLocked) {
                Helpers::logError("Account locked due to failed attempts: $username");
                throw new Exception('Account locked due to too many failed attempts. Please contact administrator.');
            }

            Helpers::logError("Failed login attempt for username: $username (Attempt $failedAttempts/5)");
            throw new Exception('Invalid credentials');
        }

        // NEW: Reset failed attempts on successful login
        $orm->update('user_authentication', [
            'FailedLoginAttempts' => 0,
            'LastLoginAt' => date('Y-m-d H:i:s'),
            'LastLoginIP' => $_SERVER['REMOTE_ADDR'] ?? null
        ], ['UserID' => $user['UserID']]);

        // Get all active roles of user with date validation
        $roles = $orm->runQuery(
            "SELECT cr.RoleName FROM member_role mr 
             JOIN church_role cr ON mr.RoleID = cr.RoleID 
             WHERE mr.MbrID = :id 
             AND mr.IsActive = 1
             AND (mr.StartDate IS NULL OR mr.StartDate <= CURDATE())
             AND (mr.EndDate IS NULL OR mr.EndDate >= CURDATE())
             AND cr.IsActive = 1",
            [':id' => $user['MbrID']]
        );

        $roleNames = array_column($roles, 'RoleName');

        $userData = [
            'MbrID'    => $user['MbrID'],
            'Username' => $user['Username'],
            'Role'     => $roleNames
        ];

        $refreshToken = self::generateRefreshToken($userData);
        self::storeRefreshToken($user['UserID'], $refreshToken);

        // Store refresh token in HttpOnly cookie (secure)
        if ($useSecureCookies) {
            TokenManager::setRefreshTokenCookie($refreshToken, $remember);
        }

        // Generate CSRF token for cookie-based auth
        $csrfToken = TokenManager::generateCsrfToken();

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

        // NEW: Look up by token hash
        $tokenHash = hash('sha256', $refreshToken);
        $stored = $orm->getWhere('user_sessions', [
            'TokenHash' => $tokenHash,
            'IsRevoked' => 0
        ]);

        if (empty($stored)) {
            Helpers::sendError('Refresh token revoked or invalid');
        }

        $tokenRecord = $stored[0];

        if (strtotime($tokenRecord['ExpiresAt']) < time()) {
            $orm->update('user_sessions', [
                'IsRevoked' => 1,
                'RevokedAt' => date('Y-m-d H:i:s')
            ], ['SessionID' => $tokenRecord['SessionID']]);
            Helpers::sendError('Refresh token expired');
        }

        // Revoke old refresh token
        $orm->update('user_sessions', [
            'IsRevoked' => 1,
            'RevokedAt' => date('Y-m-d H:i:s')
        ], ['SessionID' => $tokenRecord['SessionID']]);

        // Fetch fresh user roles with validation
        $roles = $orm->runQuery(
            "SELECT cr.RoleName FROM member_role mr 
             JOIN church_role cr ON mr.RoleID = cr.RoleID 
             WHERE mr.MbrID = :id 
             AND mr.IsActive = 1
             AND (mr.StartDate IS NULL OR mr.StartDate <= CURDATE())
             AND (mr.EndDate IS NULL OR mr.EndDate >= CURDATE())
             AND cr.IsActive = 1",
            [':id' => $decoded['user_id']]
        );

        $userData = [
            'MbrID'    => $decoded['user_id'],
            'Username' => $decoded['username'],
            'Role'     => array_column($roles, 'RoleName')
        ];

        $newRefreshToken = self::generateRefreshToken($userData);

        // Get UserID from MbrID
        $userAuth = $orm->getWhere('user_authentication', ['MbrID' => $userData['MbrID']])[0] ?? null;
        if ($userAuth) {
            self::storeRefreshToken($userAuth['UserID'], $newRefreshToken);
        }

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
            $tokenHash = hash('sha256', $refreshToken);
            $orm->update('user_sessions', [
                'IsRevoked' => 1,
                'RevokedAt' => date('Y-m-d H:i:s')
            ], ['TokenHash' => $tokenHash]);
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

        // Delete sessions expired more than 7 days ago
        $result = $orm->runQuery(
            "DELETE FROM user_sessions 
             WHERE ExpiresAt < DATE_SUB(NOW(), INTERVAL 7 DAY)",
            []
        );

        return $result ? count($result) : 0;
    }

    /**
     * Get user's active sessions
     *
     * @param int $userId User ID
     * @return array Active sessions
     */
    public static function getUserSessions(int $userId): array
    {
        $orm = new ORM();

        return $orm->runQuery(
            "SELECT SessionID, DeviceInfo, IPAddress, CreatedAt, ExpiresAt
             FROM user_sessions
             WHERE UserID = :user_id
             AND IsRevoked = 0
             AND ExpiresAt > NOW()
             ORDER BY CreatedAt DESC",
            [':user_id' => $userId]
        );
    }

    /**
     * Revoke a specific session
     *
     * @param int $sessionId Session ID
     * @param int $userId User ID (for security check)
     * @return bool Success
     */
    public static function revokeSession(int $sessionId, int $userId): bool
    {
        $orm = new ORM();

        $affected = $orm->update('user_sessions', [
            'IsRevoked' => 1,
            'RevokedAt' => date('Y-m-d H:i:s')
        ], [
            'SessionID' => $sessionId,
            'UserID' => $userId
        ]);

        return $affected > 0;
    }

    /**
     * Revoke all sessions for a user (except current)
     *
     * @param int $userId User ID
     * @param string|null $currentToken Current refresh token to keep
     * @return int Number of sessions revoked
     */
    public static function revokeAllSessions(int $userId, ?string $currentToken = null): int
    {
        $orm = new ORM();

        $conditions = "UserID = :user_id AND IsRevoked = 0";
        $params = [':user_id' => $userId];

        if ($currentToken) {
            $tokenHash = hash('sha256', $currentToken);
            $conditions .= " AND TokenHash != :token_hash";
            $params[':token_hash'] = $tokenHash;
        }

        $result = $orm->runQuery(
            "UPDATE user_sessions 
             SET IsRevoked = 1, RevokedAt = NOW()
             WHERE $conditions",
            $params
        );

        return $result ? count($result) : 0;
    }
}