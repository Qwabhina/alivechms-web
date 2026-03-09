<?php

/**
 * Authentication & Authorization Manager
 *
 * Orchestrates identity workflows and delegates data persistence to AuthRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Identity;

use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Security\TokenManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class Auth
{
    private const ACCESS_TOKEN_TTL = 1800;  // 30 minutes
    private const REFRESH_TOKEN_TTL = 86400; // 24 hours

    private static ?string $secretKey = null;
    private static ?string $refreshSecretKey = null;

    private static function initKeys(): void
    {
        if (self::$secretKey !== null)
            return;

        if (!isset($_ENV['JWT_SECRET']) || !isset($_ENV['JWT_REFRESH_SECRET'])) {
            throw new Exception('JWT secrets not configured.');
        }

        self::$secretKey = trim($_ENV['JWT_SECRET']);
        self::$refreshSecretKey = trim($_ENV['JWT_REFRESH_SECRET']);
    }

    public static function generateAccessToken(array $user): string
    {
        self::initKeys();
        return self::generateToken($user, self::$secretKey, self::ACCESS_TOKEN_TTL);
    }

    public static function generateRefreshToken(array $user): string
    {
        self::initKeys();
        return self::generateToken($user, self::$refreshSecretKey, self::REFRESH_TOKEN_TTL);
    }

    private static function generateToken(array $user, string $secret, int $ttl): string
    {
        $issuedAt = time();
        return JWT::encode([
            'iat' => $issuedAt,
            'exp' => $issuedAt + $ttl,
            'user_id' => $user['MbrID'],
            'username' => $user['Username'],
            'role' => $user['Role'] ?? [],
        ], $secret, 'HS256');
    }

    public static function verify(string $token, ?string $secret = null)
    {
        self::initKeys();
        try {
            return (array) JWT::decode($token, new Key($secret ?? self::$secretKey, 'HS256'));
        } catch (Exception $e) {
            Helpers::logError("[Auth] Token verification failed: " . $e->getMessage());
            return false;
        }
    }

    public static function login(string $username, string $password, bool $remember = false, bool $useSecureCookies = true): array
    {
        $repo = new AuthRepository();
        $user = $repo->findUserByUsername($username);

        if (!$user || !password_verify($password, $user['PasswordHash'])) {
            if ($user) {
                $maxAttempts = 5;
                $failed = ($user['FailedLoginAttempts'] ?? 0) + 1;
                $remaining = $maxAttempts - $failed;

                $repo->updateLoginMetadata((int) $user['UserID'], [
                    'FailedLoginAttempts' => $failed,
                    'IsLocked' => $failed >= $maxAttempts ? 1 : 0
                ]);

                if ($failed >= $maxAttempts) {
                    throw new Exception('Account locked due to too many failed attempts.');
                }

                throw new Exception("Invalid credentials. You have $remaining attempts left.");
            }
            throw new Exception('Invalid credentials');
        }

        if ($user['IsLocked'] || $user['MembershipStatus'] !== 'Active') {
            throw new Exception('Account locked or inactive.');
        }

        $repo->updateLoginMetadata((int) $user['UserID'], [
            'FailedLoginAttempts' => 0,
            'LastLoginAt' => date('Y-m-d H:i:s'),
            'LastLoginIP' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $roles = RBAC::getUserRoles((int) $user['MbrID']);
        $roleNames = array_column($roles, 'RoleName');

        $userData = ['MbrID' => $user['MbrID'], 'Username' => $user['Username'], 'Role' => $roleNames];
        $refreshToken = self::generateRefreshToken($userData);

        $decoded = JWT::decode($refreshToken, new Key(self::$refreshSecretKey, 'HS256'));
        $repo->createSession([
            'UserID' => $user['UserID'],
            'TokenHash' => hash('sha256', $refreshToken),
            'IPAddress' => $_SERVER['REMOTE_ADDR'] ?? null,
            'UserAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ExpiresAt' => date('Y-m-d H:i:s', $decoded->exp)
        ]);

        if ($useSecureCookies)
            TokenManager::setRefreshTokenCookie($refreshToken, $remember);

        $user['permissions'] = RBAC::getUserPermissions((int) $user['MbrID']);
        unset($user['PasswordHash']);

        return [
            'access_token' => self::generateAccessToken($userData),
            'csrf_token' => TokenManager::generateCsrfToken(),
            'user' => $user,
            'refresh_token' => $useSecureCookies ? null : $refreshToken
        ];
    }

    public static function refreshAccessToken(?string $refreshToken = null): array
    {
        self::initKeys();
        $refreshToken ??= TokenManager::getRefreshTokenFromCookie();
        if (empty($refreshToken))
            ResponseHelper::error('Refresh token required');

        $decoded = self::verify($refreshToken, self::$refreshSecretKey);
        if (!$decoded)
            ResponseHelper::error('Invalid refresh token');

        $repo = new AuthRepository();
        $session = $repo->findSessionByHash(hash('sha256', $refreshToken));

        if (!$session || strtotime($session['ExpiresAt']) < time()) {
            if ($session)
                $repo->revokeSession((int) $session['SessionID']);
            ResponseHelper::error('Session expired or revoked');
        }

        $repo->revokeSession((int) $session['SessionID']); // Rotate refresh token

        $roles = RBAC::getUserRoles((int) $decoded['user_id']);
        $userData = ['MbrID' => $decoded['user_id'], 'Username' => $decoded['username'], 'Role' => array_column($roles, 'RoleName')];

        $newRefreshToken = self::generateRefreshToken($userData);
        $newDecoded = JWT::decode($newRefreshToken, new Key(self::$refreshSecretKey, 'HS256'));

        $repo->createSession([
            'UserID' => $session['UserID'],
            'TokenHash' => hash('sha256', $newRefreshToken),
            'IPAddress' => $_SERVER['REMOTE_ADDR'] ?? null,
            'UserAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ExpiresAt' => date('Y-m-d H:i:s', $newDecoded->exp)
        ]);

        TokenManager::setRefreshTokenCookie($newRefreshToken);

        return [
            'access_token' => self::generateAccessToken($userData),
            'csrf_token' => TokenManager::generateCsrfToken()
        ];
    }

    public static function logout(?string $refreshToken = null): void
    {
        $refreshToken ??= TokenManager::getRefreshTokenFromCookie();
        if ($refreshToken) {
            (new AuthRepository())->revokeSession((int) (new AuthRepository())->findSessionByHash(hash('sha256', $refreshToken))['SessionID'] ?? 0);
        }
        TokenManager::clearRefreshTokenCookie();
    }

    public static function getCurrentUserId(): int
    {
        $token = self::getBearerToken();
        if (!$token)
            throw new Exception('Unauthorized');
        $decoded = self::verify($token);
        if (!$decoded)
            throw new Exception('Invalid token');
        return (int) $decoded['user_id'];
    }

    public static function getUserBranchId(): int
    {
        $userId = self::getCurrentUserId();
        $user = (new AuthRepository())->findUserByUsername(self::verify(self::getBearerToken())['username']);
        return (int) $user['BranchID'];
    }

    public static function checkPermission(string $permission): void
    {
        $userId = self::getCurrentUserId();
        if (!RBAC::hasPermission($userId, $permission)) {
            throw new Exception("Permission denied: $permission");
        }
    }

    public static function getUserSessions(int $userId): array
    {
        return (new AuthRepository())->getUserSessions($userId);
    }

    public static function revokeSession(int $sessionId, int $userId): bool
    {
        $repo = new AuthRepository();
        $session = $repo->findSessionById($sessionId);
        if (!$session || (int) $session['UserID'] !== $userId) {
            return false;
        }
        return $repo->revokeSession($sessionId) > 0;
    }

    public static function revokeAllSessions(int $userId, ?string $exceptRefreshToken = null): int
    {
        $hash = $exceptRefreshToken ? hash('sha256', $exceptRefreshToken) : null;
        return (new AuthRepository())->revokeSessionsByUserId($userId, $hash);
    }

    public static function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? null) : null);
        if ($header && preg_match('/Bearer\s+(\S+)/i', $header, $matches))
            return $matches[1];
        return null;
    }
}