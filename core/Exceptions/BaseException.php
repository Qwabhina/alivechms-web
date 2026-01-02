<?php

/**
 * Base Exception Class
 *
 * Base class for all custom exceptions in the AliveChMS system.
 * Provides consistent error handling with correlation IDs and context.
 *
 * @package  AliveChMS\Core\Exceptions
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

class BaseException extends Exception
{
    protected string $errorCode;
    protected array $context;
    protected string $correlationId;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        string $errorCode = '',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorCode = $errorCode ?: $this->getDefaultErrorCode();
        $this->context = $context;
        $this->correlationId = $this->generateCorrelationId();
    }

    /**
     * Get error code for client handling
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get error context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get correlation ID for tracking
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Get default error code (override in subclasses)
     */
    protected function getDefaultErrorCode(): string
    {
        return 'GENERAL_ERROR';
    }

    /**
     * Generate unique correlation ID
     */
    private function generateCorrelationId(): string
    {
        return uniqid('err_', true);
    }

    /**
     * Convert exception to array for logging/response
     */
    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'correlation_id' => $this->correlationId,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'timestamp' => date('c')
        ];
    }
}