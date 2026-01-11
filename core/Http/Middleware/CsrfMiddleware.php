<?php

/**
 * CSRF Middleware
 *
 * Provides Cross-Site Request Forgery protection by validating CSRF tokens
 * on state-changing requests (POST, PUT, PATCH, DELETE).
 *
 * @package  AliveChMS\Core\Http\Middleware
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../../Security/CsrfProtection.php';

class CsrfMiddleware extends Middleware
{
    private array $except;
    private bool $enabled;

    public function __construct(array $except = [], bool $enabled = true)
    {
        $this->except = $except;
        $this->enabled = $enabled;
    }

    public function handle(Request $request, callable $next): Response
    {
        // Skip if CSRF protection is disabled
        if (!$this->enabled) {
            return $next($request);
        }

        // Skip if request method doesn't require protection
        if (!CsrfProtection::requiresProtection($request->getMethod())) {
            return $next($request);
        }

        // Skip if route is in exception list
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Verify CSRF token
        if (!CsrfProtection::verifyRequest($request)) {
            return $this->buildCsrfErrorResponse();
        }

        return $next($request);
    }

    public function getPriority(): int
    {
        return 80; // Execute after auth but before business logic
    }

    /**
     * Check if request should skip CSRF protection
     */
    private function shouldSkip(Request $request): bool
    {
        $path = $request->getPath();
        
        foreach ($this->except as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if path matches pattern
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
        $regex = '/^' . $regex . '$/';
        
        return preg_match($regex, $path) === 1;
    }

    /**
     * Build CSRF error response
     */
    private function buildCsrfErrorResponse(): Response
    {
        return Response::error('CSRF token mismatch', 419, [
            'error' => 'CSRF_TOKEN_MISMATCH',
            'message' => 'The CSRF token is invalid or missing. Please refresh the page and try again.'
        ]);
    }

    /**
     * Create middleware with common API exceptions
     */
    public static function forApi(array $additionalExcept = []): self
    {
        $defaultExcept = [
            '/api/auth/login',
            '/api/auth/register',
            '/api/health',
            '/api/status'
        ];

        return new self(array_merge($defaultExcept, $additionalExcept));
    }

    /**
     * Create middleware for web routes
     */
    public static function forWeb(array $except = []): self
    {
        return new self($except);
    }

    /**
     * Disable CSRF protection (for testing)
     */
    public static function disabled(): self
    {
        return new self([], false);
    }
}