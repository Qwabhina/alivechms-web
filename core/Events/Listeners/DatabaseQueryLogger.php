<?php

/**
 * Database Query Logger
 *
 * Logs database queries for performance monitoring and debugging.
 *
 * @package  AliveChMS\Core\Events\Listeners
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../EventListener.php';
require_once __DIR__ . '/../SystemEvents.php';

class DatabaseQueryLogger extends AbstractEventListener
{
    private string $logFile;
    private float $slowQueryThreshold;
    private bool $logAllQueries;

    public function __construct(
        string $logFile = null,
        float $slowQueryThreshold = 1.0,
        bool $logAllQueries = false
    ) {
        $this->logFile = $logFile ?? __DIR__ . '/../../../logs/database.log';
        $this->slowQueryThreshold = $slowQueryThreshold;
        $this->logAllQueries = $logAllQueries;
        $this->ensureLogDirectory();
    }

    public function handle(Event $event): void
    {
        if (!($event instanceof DatabaseQueryEvent)) {
            return;
        }

        // Only log slow queries unless configured to log all
        if (!$this->logAllQueries && !$event->isSlow($this->slowQueryThreshold)) {
            return;
        }

        $logEntry = $this->formatLogEntry($event);
        $this->writeLog($logEntry);
    }

    public function shouldHandle(Event $event): bool
    {
        return $event instanceof DatabaseQueryEvent;
    }

    public function getPriority(): int
    {
        return 100; // Normal priority
    }

    private function formatLogEntry(DatabaseQueryEvent $event): string
    {
        $timestamp = date('Y-m-d H:i:s', (int)$event->getTimestamp());
        $duration = number_format($event->getDuration() * 1000, 2); // Convert to milliseconds
        $query = $this->sanitizeQuery($event->getQuery());
        $bindings = $event->getBindings();
        $eventId = $event->getEventId();

        $logEntry = "[$timestamp] Query ($eventId) - {$duration}ms: $query";
        
        if (!empty($bindings)) {
            $bindingsStr = json_encode($bindings, JSON_UNESCAPED_SLASHES);
            $logEntry .= " | Bindings: $bindingsStr";
        }

        if ($event->isSlow($this->slowQueryThreshold)) {
            $logEntry .= " | SLOW QUERY";
        }

        return $logEntry . "\n";
    }

    private function sanitizeQuery(string $query): string
    {
        // Remove extra whitespace and newlines
        $query = preg_replace('/\s+/', ' ', $query);
        return trim($query);
    }

    private function writeLog(string $entry): void
    {
        file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
}