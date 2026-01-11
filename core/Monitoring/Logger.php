<?php

/**
 * Enhanced Logger
 *
 * Provides structured logging with multiple levels, contexts, and output formats.
 * Supports file logging, error tracking, and performance monitoring.
 *
 * @package  AliveChMS\Core\Monitoring
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

class Logger
{
    // Log levels (RFC 5424)
    public const EMERGENCY = 0;
    public const ALERT = 1;
    public const CRITICAL = 2;
    public const ERROR = 3;
    public const WARNING = 4;
    public const NOTICE = 5;
    public const INFO = 6;
    public const DEBUG = 7;

    private static array $levelNames = [
        self::EMERGENCY => 'EMERGENCY',
        self::ALERT => 'ALERT',
        self::CRITICAL => 'CRITICAL',
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG'
    ];

    private static ?self $instance = null;
    private string $logDir;
    private int $minLevel;
    private array $handlers = [];
    private array $processors = [];

    public function __construct(string $logDir = null, int $minLevel = self::INFO)
    {
        $this->logDir = $logDir ?: __DIR__ . '/../../logs';
        $this->minLevel = $minLevel;
        
        $this->ensureLogDirectory();
        $this->registerDefaultHandlers();
        $this->registerDefaultProcessors();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log emergency message
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::getInstance()->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log alert message
     */
    public static function alert(string $message, array $context = []): void
    {
        self::getInstance()->log(self::ALERT, $message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->log(self::ERROR, $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->log(self::WARNING, $message, $context);
    }

    /**
     * Log notice message
     */
    public static function notice(string $message, array $context = []): void
    {
        self::getInstance()->log(self::NOTICE, $message, $context);
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->log(self::INFO, $message, $context);
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->log(self::DEBUG, $message, $context);
    }

    /**
     * Log message with specified level
     */
    public function log(int $level, string $message, array $context = []): void
    {
        if ($level > $this->minLevel) {
            return;
        }

        $record = $this->createRecord($level, $message, $context);
        
        // Process record through processors
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }

        // Send to handlers
        foreach ($this->handlers as $handler) {
            $handler($record);
        }
    }

    /**
     * Add log handler
     */
    public function addHandler(callable $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Add log processor
     */
    public function addProcessor(callable $processor): void
    {
        $this->processors[] = $processor;
    }

    /**
     * Set minimum log level
     */
    public function setMinLevel(int $level): void
    {
        $this->minLevel = $level;
    }

    /**
     * Create log record
     */
    private function createRecord(int $level, string $message, array $context): array
    {
        return [
            'timestamp' => microtime(true),
            'datetime' => date('c'),
            'level' => $level,
            'level_name' => self::$levelNames[$level] ?? 'UNKNOWN',
            'message' => $message,
            'context' => $context,
            'extra' => []
        ];
    }

    /**
     * Register default handlers
     */
    private function registerDefaultHandlers(): void
    {
        // File handler
        $this->addHandler(function($record) {
            $this->writeToFile($record);
        });

        // Error log handler for critical messages
        $this->addHandler(function($record) {
            if ($record['level'] <= self::ERROR) {
                error_log($this->formatRecord($record));
            }
        });
    }

    /**
     * Register default processors
     */
    private function registerDefaultProcessors(): void
    {
        // Add request context
        $this->addProcessor(function($record) {
            $record['extra']['request_id'] = $this->getRequestId();
            $record['extra']['user_id'] = $this->getCurrentUserId();
            $record['extra']['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $record['extra']['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $record['extra']['memory_usage'] = memory_get_usage(true);
            return $record;
        });

        // Add performance context
        $this->addProcessor(function($record) {
            if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
                $record['extra']['request_duration'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            }
            return $record;
        });
    }

    /**
     * Write record to file
     */
    private function writeToFile(array $record): void
    {
        $filename = $this->getLogFilename($record['level']);
        $formatted = $this->formatRecord($record);
        
        file_put_contents($filename, $formatted . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Get log filename based on level and date
     */
    private function getLogFilename(int $level): string
    {
        $date = date('Y-m-d');
        $levelName = strtolower(self::$levelNames[$level] ?? 'unknown');
        
        return $this->logDir . "/alivechms-{$levelName}-{$date}.log";
    }

    /**
     * Format log record
     */
    private function formatRecord(array $record): string
    {
        $formatted = sprintf(
            '[%s] %s: %s',
            $record['datetime'],
            $record['level_name'],
            $record['message']
        );

        // Add context if present
        if (!empty($record['context'])) {
            $formatted .= ' ' . json_encode($record['context'], JSON_UNESCAPED_SLASHES);
        }

        // Add extra data if present
        if (!empty($record['extra'])) {
            $formatted .= ' ' . json_encode($record['extra'], JSON_UNESCAPED_SLASHES);
        }

        return $formatted;
    }

    /**
     * Get or generate request ID
     */
    private function getRequestId(): string
    {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid('req_', true);
        }
        
        return $requestId;
    }

    /**
     * Get current user ID
     */
    private function getCurrentUserId(): ?int
    {
        // Try to get from session
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        // Try to get from JWT token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            
            try {
                if (class_exists('Auth')) {
                    $decoded = Auth::verify($token);
                    if ($decoded && isset($decoded['user_id'])) {
                        return (int)$decoded['user_id'];
                    }
                }
            } catch (Exception $e) {
                // Ignore token verification errors in logging
            }
        }

        return null;
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Log HTTP request
     */
    public static function logRequest(string $method, string $uri, int $statusCode, float $duration): void
    {
        self::info('HTTP Request', [
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
    }

    /**
     * Log database query
     */
    public static function logQuery(string $query, array $bindings = [], float $duration = 0): void
    {
        self::debug('Database Query', [
            'query' => $query,
            'bindings' => $bindings,
            'duration_ms' => round($duration * 1000, 2)
        ]);
    }

    /**
     * Log cache operation
     */
    public static function logCache(string $operation, string $key, bool $hit = null, float $duration = 0): void
    {
        self::debug('Cache Operation', [
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'duration_ms' => round($duration * 1000, 2)
        ]);
    }

    /**
     * Log authentication event
     */
    public static function logAuth(string $event, int $userId = null, array $context = []): void
    {
        self::info('Authentication Event', array_merge([
            'event' => $event,
            'user_id' => $userId
        ], $context));
    }

    /**
     * Log security event
     */
    public static function logSecurity(string $event, array $context = []): void
    {
        self::warning('Security Event', array_merge([
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $context));
    }
}