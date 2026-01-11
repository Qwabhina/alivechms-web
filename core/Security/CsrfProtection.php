<?php

/**
 * CSRF Protection
 *
 * Provides Cross-Site Request Forgery protection through token generation
 * and validation. Integrates with the session system and middleware pipeline.
 *
 * @package  AliveChMS\Core\Security
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

class CsrfProtection
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = '_csrf_token';
    private const HEADER_NAME = 'X-CSRF-TOKEN';
    private const FIELD_NAME = '_token';

    /**
     * Generate a new CSRF token
     */
    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        self::storeToken($token);
        return $token;
    }

    /**
     * Get current CSRF token (generate if none exists)
     */
    public static function getToken(): string
    {
        $token = self::getStoredToken();
        
        if (!$token) {
            $token = self::generateToken();
        }
        
        return $token;
    }

    /**
     * Validate CSRF token from request
     */
    public static function validateToken(string $token): bool
    {
        $storedToken = self::getStoredToken();
        
        if (!$storedToken || !$token) {
            return false;
        }
        
        return hash_equals($storedToken, $token);
    }

    /**
     * Extract token from request
     */
    public static function getTokenFromRequest(Request $request): ?string
    {
        // Check header first
        $token = $request->getHeader(self::HEADER_NAME);
        
        if ($token) {
            return $token;
        }
        
        // Check POST data
        $token = $request->input(self::FIELD_NAME);
        
        if ($token) {
            return $token;
        }
        
        // Check JSON body
        $json = $request->json();
        if (is_array($json) && isset($json[self::FIELD_NAME])) {
            return $json[self::FIELD_NAME];
        }
        
        return null;
    }

    /**
     * Verify request has valid CSRF token
     */
    public static function verifyRequest(Request $request): bool
    {
        $token = self::getTokenFromRequest($request);
        return $token ? self::validateToken($token) : false;
    }

    /**
     * Check if request method requires CSRF protection
     */
    public static function requiresProtection(string $method): bool
    {
        return in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    /**
     * Generate HTML input field for forms
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Generate meta tag for AJAX requests
     */
    public static function metaTag(): string
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get token for JavaScript/AJAX usage
     */
    public static function getTokenForJs(): array
    {
        return [
            'token' => self::getToken(),
            'header' => self::HEADER_NAME,
            'field' => self::FIELD_NAME
        ];
    }

    /**
     * Store token in session
     */
    private static function storeToken(string $token): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::SESSION_KEY] = $token;
        }
    }

    /**
     * Get stored token from session
     */
    private static function getStoredToken(): ?string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        return (session_status() === PHP_SESSION_ACTIVE) ? ($_SESSION[self::SESSION_KEY] ?? null) : null;
    }

    /**
     * Clear stored token
     */
    public static function clearToken(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    /**
     * Regenerate token (useful after login/logout)
     */
    public static function regenerateToken(): string
    {
        self::clearToken();
        return self::generateToken();
    }
}