<?php

/**
 * User Activity Logger
 *
 * Logs user activities for audit and security purposes.
 *
 * @package  AliveChMS\Core\Events\Listeners
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../EventListener.php';
require_once __DIR__ . '/../UserEvents.php';

class UserActivityLogger extends AbstractEventListener
{
    private string $logFile;

    public function __construct(string $logFile = null)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../../logs/user_activity.log';
        $this->ensureLogDirectory();
    }

    public function handle(Event $event): void
    {
        $logEntry = $this->formatLogEntry($event);
        $this->writeLog($logEntry);
    }

    public function shouldHandle(Event $event): bool
    {
        return $event instanceof UserLoginEvent ||
               $event instanceof UserLogoutEvent ||
               $event instanceof UserRegistrationEvent ||
               $event instanceof UserProfileUpdateEvent ||
               $event instanceof PasswordChangeEvent;
    }

    public function getPriority(): int
    {
        return 200; // High priority for security logging
    }

    private function formatLogEntry(Event $event): string
    {
        $timestamp = date('Y-m-d H:i:s', (int)$event->getTimestamp());
        $eventName = $event->getName();
        $eventId = $event->getEventId();

        $details = match (true) {
            $event instanceof UserLoginEvent => $this->formatLoginEvent($event),
            $event instanceof UserLogoutEvent => $this->formatLogoutEvent($event),
            $event instanceof UserRegistrationEvent => $this->formatRegistrationEvent($event),
            $event instanceof UserProfileUpdateEvent => $this->formatProfileUpdateEvent($event),
            $event instanceof PasswordChangeEvent => $this->formatPasswordChangeEvent($event),
            default => 'Unknown user event'
        };

        return "[$timestamp] $eventName ($eventId): $details\n";
    }

    private function formatLoginEvent(UserLoginEvent $event): string
    {
        $user = $event->getUser();
        $method = $event->getLoginMethod();
        $ip = $event->getIpAddress();
        
        return "User {$user['username']} (ID: {$user['id']}) logged in via $method from $ip";
    }

    private function formatLogoutEvent(UserLogoutEvent $event): string
    {
        $user = $event->getUser();
        $reason = $event->getReason();
        $ip = $event->getData('ip_address');
        
        return "User {$user['username']} (ID: {$user['id']}) logged out ($reason) from $ip";
    }

    private function formatRegistrationEvent(UserRegistrationEvent $event): string
    {
        $user = $event->getUser();
        $ip = $event->getData('ip_address');
        
        return "New user registered: {$user['username']} ({$user['email']}) from $ip";
    }

    private function formatProfileUpdateEvent(UserProfileUpdateEvent $event): string
    {
        $user = $event->getUser();
        $changes = array_keys($event->getChanges());
        $changedFields = implode(', ', $changes);
        
        return "User {$user['username']} (ID: {$user['id']}) updated profile fields: $changedFields";
    }

    private function formatPasswordChangeEvent(PasswordChangeEvent $event): string
    {
        $userId = $event->getUserId();
        $forced = $event->wasForced() ? 'forced' : 'voluntary';
        $ip = $event->getData('ip_address');
        
        return "User ID $userId changed password ($forced) from $ip";
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