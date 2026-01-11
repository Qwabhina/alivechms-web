<?php

/**
 * Error Notifier
 *
 * Handles error events by logging and optionally sending notifications
 * for critical errors.
 *
 * @package  AliveChMS\Core\Events\Listeners
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../EventListener.php';
require_once __DIR__ . '/../SystemEvents.php';

class ErrorNotifier extends AbstractEventListener
{
    private string $logFile;
    private array $notificationConfig;
    private array $criticalErrorTypes;

    public function __construct(array $config = [])
    {
        $this->logFile = $config['log_file'] ?? __DIR__ . '/../../../logs/errors.log';
        $this->notificationConfig = $config['notifications'] ?? [];
        $this->criticalErrorTypes = $config['critical_types'] ?? ['critical', 'error'];
        $this->ensureLogDirectory();
    }

    public function handle(Event $event): void
    {
        if (!($event instanceof ErrorEvent)) {
            return;
        }

        // Always log the error
        $this->logError($event);

        // Send notification for critical errors
        if ($this->isCriticalError($event)) {
            $this->sendNotification($event);
        }
    }

    public function shouldHandle(Event $event): bool
    {
        return $event instanceof ErrorEvent;
    }

    public function getPriority(): int
    {
        return 300; // Very high priority for error handling
    }

    private function logError(ErrorEvent $event): void
    {
        $timestamp = date('Y-m-d H:i:s', (int)$event->getTimestamp());
        $severity = strtoupper($event->getSeverity());
        $message = $event->getMessage();
        $file = $event->getData('file');
        $line = $event->getData('line');
        $eventId = $event->getEventId();

        $logEntry = "[$timestamp] $severity ($eventId): $message in $file:$line\n";
        
        // Add context if available
        $context = $event->getContext();
        if (!empty($context)) {
            $contextStr = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $logEntry .= "Context: $contextStr\n";
        }

        // Add stack trace for critical errors
        if ($this->isCriticalError($event)) {
            $trace = $event->getData('trace');
            $logEntry .= "Stack Trace:\n$trace\n";
        }

        $logEntry .= str_repeat('-', 80) . "\n";

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function sendNotification(ErrorEvent $event): void
    {
        if (empty($this->notificationConfig)) {
            return;
        }

        $notification = [
            'subject' => 'Critical Error in AliveChMS',
            'message' => $this->formatNotificationMessage($event),
            'severity' => $event->getSeverity(),
            'timestamp' => date('Y-m-d H:i:s', (int)$event->getTimestamp())
        ];

        // Here you could integrate with email, Slack, SMS, etc.
        // For now, we'll just log the notification
        $this->logNotification($notification);
    }

    private function formatNotificationMessage(ErrorEvent $event): string
    {
        $message = $event->getMessage();
        $file = $event->getData('file');
        $line = $event->getData('line');
        $severity = $event->getSeverity();

        return "A $severity error occurred in AliveChMS:\n\n" .
               "Error: $message\n" .
               "File: $file:$line\n" .
               "Time: " . date('Y-m-d H:i:s', (int)$event->getTimestamp()) . "\n" .
               "Event ID: " . $event->getEventId();
    }

    private function logNotification(array $notification): void
    {
        $notificationLog = dirname($this->logFile) . '/notifications.log';
        $entry = "[" . $notification['timestamp'] . "] " . 
                 $notification['subject'] . "\n" . 
                 $notification['message'] . "\n" . 
                 str_repeat('=', 80) . "\n";
        
        file_put_contents($notificationLog, $entry, FILE_APPEND | LOCK_EX);
    }

    private function isCriticalError(ErrorEvent $event): bool
    {
        return in_array($event->getSeverity(), $this->criticalErrorTypes);
    }

    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
}