<?php

/**
 * Event Service Provider
 *
 * Registers the event system and default event listeners with the
 * dependency injection container.
 *
 * @package  AliveChMS\Core\Providers
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../ServiceProvider.php';
require_once __DIR__ . '/../Events/EventDispatcher.php';
require_once __DIR__ . '/../Events/Listeners/UserActivityLogger.php';
require_once __DIR__ . '/../Events/Listeners/DatabaseQueryLogger.php';
require_once __DIR__ . '/../Events/Listeners/ErrorNotifier.php';

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register EventDispatcher as singleton
        $this->container->singleton('EventDispatcher', function ($container) {
            return EventDispatcher::getInstance();
        });

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Boot services
     */
    public function boot(): void
    {
        $dispatcher = $this->container->resolve('EventDispatcher');
        
        // Register default event listeners
        $this->registerDefaultListeners($dispatcher);
        
        // Register wildcard listeners
        $this->registerWildcardListeners($dispatcher);
    }

    /**
     * Register event listener services
     */
    private function registerEventListeners(): void
    {
        // User Activity Logger
        $this->container->singleton('UserActivityLogger', function ($container) {
            return new UserActivityLogger();
        });

        // Database Query Logger
        $this->container->singleton('DatabaseQueryLogger', function ($container) {
            $config = $this->getQueryLoggerConfig();
            return new DatabaseQueryLogger(
                $config['log_file'] ?? null,
                $config['slow_query_threshold'] ?? 1.0,
                $config['log_all_queries'] ?? false
            );
        });

        // Error Notifier
        $this->container->singleton('ErrorNotifier', function ($container) {
            $config = $this->getErrorNotifierConfig();
            return new ErrorNotifier($config);
        });
    }

    /**
     * Register default event listeners
     */
    private function registerDefaultListeners(EventDispatcher $dispatcher): void
    {
        // User activity logging
        $userActivityLogger = $this->container->resolve('UserActivityLogger');
        $dispatcher->listen('UserLoginEvent', $userActivityLogger);
        $dispatcher->listen('UserLogoutEvent', $userActivityLogger);
        $dispatcher->listen('UserRegistrationEvent', $userActivityLogger);
        $dispatcher->listen('UserProfileUpdateEvent', $userActivityLogger);
        $dispatcher->listen('PasswordChangeEvent', $userActivityLogger);

        // Database query logging
        $queryLogger = $this->container->resolve('DatabaseQueryLogger');
        $dispatcher->listen('DatabaseQueryEvent', $queryLogger);

        // Error handling
        $errorNotifier = $this->container->resolve('ErrorNotifier');
        $dispatcher->listen('ErrorEvent', $errorNotifier);

        // System event logging
        $this->registerSystemEventListeners($dispatcher);
    }

    /**
     * Register wildcard listeners
     */
    private function registerWildcardListeners(EventDispatcher $dispatcher): void
    {
        // Log all events for debugging (if enabled)
        if ($this->isDebugLoggingEnabled()) {
            $dispatcher->listen('*', function (Event $event) {
                $this->logDebugEvent($event);
            });
        }

        // Performance monitoring for all events
        $dispatcher->listen('*', function (Event $event) {
            $this->monitorEventPerformance($event);
        });
    }

    /**
     * Register system event listeners
     */
    private function registerSystemEventListeners(EventDispatcher $dispatcher): void
    {
        // Application started
        $dispatcher->listen('ApplicationStartedEvent', function (Event $event) {
            $this->logApplicationStart($event);
        });

        // HTTP request/response logging
        $dispatcher->listen('HttpRequestEvent', function (Event $event) {
            $this->logHttpRequest($event);
        });

        $dispatcher->listen('HttpResponseEvent', function (Event $event) {
            $this->logHttpResponse($event);
        });

        // Cache events
        $dispatcher->listen('CacheHitEvent', function (Event $event) {
            $this->logCacheEvent($event, 'HIT');
        });

        $dispatcher->listen('CacheMissEvent', function (Event $event) {
            $this->logCacheEvent($event, 'MISS');
        });
    }

    /**
     * Get query logger configuration
     */
    private function getQueryLoggerConfig(): array
    {
        return [
            'log_file' => __DIR__ . '/../../logs/database.log',
            'slow_query_threshold' => (float)($_ENV['DB_SLOW_QUERY_THRESHOLD'] ?? 1.0),
            'log_all_queries' => ($_ENV['DB_LOG_ALL_QUERIES'] ?? 'false') === 'true'
        ];
    }

    /**
     * Get error notifier configuration
     */
    private function getErrorNotifierConfig(): array
    {
        return [
            'log_file' => __DIR__ . '/../../logs/errors.log',
            'notifications' => [
                'enabled' => ($_ENV['ERROR_NOTIFICATIONS'] ?? 'false') === 'true',
                'email' => $_ENV['ERROR_NOTIFICATION_EMAIL'] ?? null,
                'webhook' => $_ENV['ERROR_NOTIFICATION_WEBHOOK'] ?? null
            ],
            'critical_types' => ['critical', 'error']
        ];
    }

    /**
     * Check if debug logging is enabled
     */
    private function isDebugLoggingEnabled(): bool
    {
        return ($_ENV['EVENT_DEBUG_LOGGING'] ?? 'false') === 'true';
    }

    /**
     * Log debug event
     */
    private function logDebugEvent(Event $event): void
    {
        $logFile = __DIR__ . '/../../logs/events_debug.log';
        $timestamp = date('Y-m-d H:i:s', (int)$event->getTimestamp());
        $eventName = $event->getName();
        $eventId = $event->getEventId();
        
        $entry = "[$timestamp] DEBUG: $eventName ($eventId)\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Monitor event performance
     */
    private function monitorEventPerformance(Event $event): void
    {
        // This could integrate with performance monitoring tools
        // For now, we'll just track slow events
        $processingTime = microtime(true) - $event->getTimestamp();
        
        if ($processingTime > 0.1) { // 100ms threshold
            $logFile = __DIR__ . '/../../logs/slow_events.log';
            $timestamp = date('Y-m-d H:i:s');
            $eventName = $event->getName();
            $duration = number_format($processingTime * 1000, 2);
            
            $entry = "[$timestamp] SLOW EVENT: $eventName took {$duration}ms\n";
            file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Log application start
     */
    private function logApplicationStart(Event $event): void
    {
        $logFile = __DIR__ . '/../../logs/application.log';
        $timestamp = date('Y-m-d H:i:s', (int)$event->getTimestamp());
        $phpVersion = $event->getData('php_version');
        
        $entry = "[$timestamp] Application started (PHP $phpVersion)\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log HTTP request
     */
    private function logHttpRequest(Event $event): void
    {
        if (!($event instanceof HttpRequestEvent)) {
            return;
        }

        $logFile = __DIR__ . '/../../logs/http_requests.log';
        $timestamp = date('Y-m-d H:i:s', (int)$event->getTimestamp());
        $method = $event->getMethod();
        $uri = $event->getUri();
        $ip = $event->getData('ip_address');
        
        $entry = "[$timestamp] $method $uri from $ip\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log HTTP response
     */
    private function logHttpResponse(Event $event): void
    {
        if (!($event instanceof HttpResponseEvent)) {
            return;
        }

        $logFile = __DIR__ . '/../../logs/http_responses.log';
        $timestamp = date('Y-m-d H:i:s', (int)$event->getTimestamp());
        $statusCode = $event->getStatusCode();
        $duration = number_format($event->getDuration() * 1000, 2);
        
        $entry = "[$timestamp] Response $statusCode ({$duration}ms)\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log cache event
     */
    private function logCacheEvent(Event $event, string $type): void
    {
        $logFile = __DIR__ . '/../../logs/cache.log';
        $timestamp = date('Y-m-d H:i:s', (int)$event->getTimestamp());
        $key = $event->getData('key');
        $store = $event->getData('store');
        
        $entry = "[$timestamp] CACHE $type: $key (store: $store)\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}