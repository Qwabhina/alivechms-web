<?php

/**
 * Validation Exception
 *
 * Thrown when input validation fails. Contains validation errors
 * and provides structured error information for API responses.
 *
 * @package  AliveChMS\Core\Exceptions
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseException.php';

class ValidationException extends BaseException
{
    private array $validationErrors;

    public function __construct(
        array $validationErrors,
        string $message = 'Validation failed',
        array $context = []
    ) {
        $this->validationErrors = $validationErrors;
        
        parent::__construct($message, 422, null, 'VALIDATION_ERROR', $context);
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get default error code
     */
    protected function getDefaultErrorCode(): string
    {
        return 'VALIDATION_ERROR';
    }

    /**
     * Convert to array with validation errors
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['validation_errors'] = $this->validationErrors;
        return $array;
    }
}