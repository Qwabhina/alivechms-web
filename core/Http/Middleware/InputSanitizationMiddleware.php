<?php

/**
 * Input Sanitization Middleware
 *
 * Automatically sanitizes all incoming request data to prevent XSS and injection attacks.
 * This middleware runs early in the request lifecycle to clean input data.
 *
 * @package  AliveChMS\Core\Http\Middleware
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../../Helpers.php';

class InputSanitizationMiddleware
{
    /**
     * Handle the incoming request and sanitize input data
     *
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, callable $next)
    {
        // Sanitize GET parameters
        if (!empty($_GET)) {
            $_GET = $this->sanitizeArray($_GET);
        }

        // Sanitize POST parameters
        if (!empty($_POST)) {
            $_POST = $this->sanitizeArray($_POST);
        }

        // Sanitize JSON input (for API requests)
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $jsonData = json_decode($input, true);
            if (is_array($jsonData)) {
                $sanitizedJson = $this->sanitizeArray($jsonData);
                // Store sanitized data for later use
                $request->setSanitizedInput($sanitizedJson);
            }
        }

        // Continue to next middleware
        return $next($request);
    }

    /**
     * Recursively sanitize array data
     *
     * @param array $data
     * @return array
     */
    private function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $cleanKey = $this->sanitizeKey($key);
            
            if (is_array($value)) {
                $sanitized[$cleanKey] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$cleanKey] = $this->sanitizeValue($value, $key);
            } else {
                // Keep non-string values as-is (numbers, booleans, etc.)
                $sanitized[$cleanKey] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize array keys
     *
     * @param string $key
     * @return string
     */
    private function sanitizeKey(string $key): string
    {
        // Remove any dangerous characters from keys
        return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $key);
    }

    /**
     * Sanitize individual values based on context
     *
     * @param string $value
     * @param string $key
     * @return string
     */
    private function sanitizeValue(string $value, string $key): string
    {
        // Determine sanitization type based on field name
        $key = strtolower($key);
        
        // Email fields
        if (strpos($key, 'email') !== false) {
            return Helpers::sanitize($value, 'email');
        }
        
        // URL fields
        if (strpos($key, 'url') !== false || strpos($key, 'website') !== false) {
            return Helpers::sanitize($value, 'url');
        }
        
        // HTML content fields (allow safe HTML)
        if (strpos($key, 'content') !== false || strpos($key, 'description') !== false || strpos($key, 'notes') !== false) {
            return Helpers::sanitize($value, 'html');
        }
        
        // Filename fields
        if (strpos($key, 'filename') !== false || strpos($key, 'file') !== false) {
            return Helpers::sanitize($value, 'filename');
        }
        
        // Numeric fields
        if (strpos($key, 'id') !== false || strpos($key, 'amount') !== false || strpos($key, 'price') !== false || strpos($key, 'quantity') !== false) {
            if (is_numeric($value)) {
                return strpos($value, '.') !== false ? Helpers::sanitize($value, 'float') : Helpers::sanitize($value, 'int');
            }
        }
        
        // Default: standard string sanitization
        return Helpers::sanitize($value, 'string');
    }

    /**
     * Check if a field should be treated as raw (no sanitization)
     * Used for fields that need to preserve exact content
     *
     * @param string $key
     * @return bool
     */
    private function isRawField(string $key): bool
    {
        $rawFields = [
            'password',
            'password_confirmation',
            'token',
            'refresh_token',
            'access_token',
            'signature',
            'hash'
        ];
        
        return in_array(strtolower($key), $rawFields);
    }
}