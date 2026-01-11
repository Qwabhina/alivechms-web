<?php

/**
 * Response Helper - Standardized Response Format
 *
 * Provides a single, consistent response format across the entire application.
 * Replaces the multiple response patterns with a unified approach.
 *
 * This class bridges the gap between old and new response systems,
 * providing backward compatibility while standardizing the format.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

class ResponseHelper
{
    /**
     * Send standardized success response
     *
     * @param mixed $data Optional data payload
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return never
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): never
    {
        http_response_code($statusCode);
        
        $response = [
            'status' => 'success',
            'message' => $message,
            'timestamp' => date('c')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send standardized error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Optional validation errors
     * @param string $errorCode Optional error code for client handling
     * @return never
     */
    public static function error(string $message, int $statusCode = 400, array $errors = [], string $errorCode = ''): never
    {
        http_response_code($statusCode);
        
        $response = [
            'status' => 'error',
            'message' => $message,
            'code' => $statusCode,
            'timestamp' => date('c')
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($errorCode)) {
            $response['error_code'] = $errorCode;
        }

        // Log error for debugging (but don't expose sensitive info)
        if ($statusCode >= 500) {
            error_log("Server Error [{$statusCode}]: {$message}");
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send paginated response
     *
     * @param array $data Data items
     * @param int $total Total number of items
     * @param int $page Current page
     * @param int $limit Items per page
     * @param string $message Optional message
     * @return never
     */
    public static function paginated(array $data, int $total, int $page, int $limit, string $message = 'Data retrieved successfully'): never
    {
        $totalPages = ceil($total / $limit);
        
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'timestamp' => date('c')
        ];

        http_response_code(200);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send validation error response
     *
     * @param array $errors Validation errors
     * @param string $message Optional custom message
     * @return never
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): never
    {
        self::error($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Send unauthorized response
     *
     * @param string $message Optional custom message
     * @return never
     */
    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        self::error($message, 401, [], 'UNAUTHORIZED');
    }

    /**
     * Send forbidden response
     *
     * @param string $message Optional custom message
     * @return never
     */
    public static function forbidden(string $message = 'Forbidden'): never
    {
        self::error($message, 403, [], 'FORBIDDEN');
    }

    /**
     * Send not found response
     *
     * @param string $message Optional custom message
     * @return never
     */
    public static function notFound(string $message = 'Resource not found'): never
    {
        self::error($message, 404, [], 'NOT_FOUND');
    }

    /**
     * Send rate limit exceeded response
     *
     * @param string $message Optional custom message
     * @param int $retryAfter Seconds to wait before retry
     * @return never
     */
    public static function rateLimited(string $message = 'Too many requests', int $retryAfter = 60): never
    {
        header("Retry-After: $retryAfter");
        self::error($message, 429, [], 'RATE_LIMITED');
    }

    /**
     * Send server error response
     *
     * @param string $message Optional custom message
     * @param string $errorCode Optional error code
     * @return never
     */
    public static function serverError(string $message = 'Internal server error', string $errorCode = 'SERVER_ERROR'): never
    {
        self::error($message, 500, [], $errorCode);
    }

    /**
     * Send created response (for POST requests)
     *
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @return never
     */
    public static function created($data, string $message = 'Resource created successfully'): never
    {
        self::success($data, $message, 201);
    }

    /**
     * Send no content response (for DELETE requests)
     *
     * @param string $message Optional message
     * @return never
     */
    public static function noContent(string $message = 'Resource deleted successfully'): never
    {
        http_response_code(204);
        exit;
    }

    /**
     * Backward compatibility method - replaces Helpers::sendFeedback
     *
     * @deprecated Use success() or error() instead
     * @param string $message Response message
     * @param int $code HTTP status code
     * @param string $type Response type ('success' or 'error')
     * @return never
     */
    public static function sendFeedback(string $message, int $code = 400, string $type = 'error'): never
    {
        if ($type === 'success') {
            self::success(null, $message, $code);
        } else {
            self::error($message, $code);
        }
    }

    /**
     * Get standardized response array (without sending)
     * Useful for testing or when you need the array format
     *
     * @param mixed $data Data payload
     * @param string $message Message
     * @param string $status Status ('success' or 'error')
     * @param int $statusCode HTTP status code
     * @param array $errors Optional errors
     * @return array Response array
     */
    public static function getResponseArray($data = null, string $message = '', string $status = 'success', int $statusCode = 200, array $errors = []): array
    {
        $response = [
            'status' => $status,
            'message' => $message,
            'timestamp' => date('c')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if ($status === 'error') {
            $response['code'] = $statusCode;
        }

        return $response;
    }
}