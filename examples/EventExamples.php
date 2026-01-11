<?php

/**
 * Event System Examples
 *
 * Demonstrates how to use the event system in AliveChMS for various
 * scenarios including user actions, system events, and custom events.
 *
 * @package  AliveChMS\Examples
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Application.php';
require_once __DIR__ . '/../core/Events/EventDispatcher.php';
require_once __DIR__ . '/../core/Events/UserEvents.php';
require_once __DIR__ . '/../core/Events/SystemEvents.php';

class EventExamples
{
    private EventDispatcher $dispatcher;

    public function __construct()
    {
        // Initialize application to get event system
        $app = Application::getInstance();
        $app->bootstrap();
        
        $this->dispatcher = $app->make('EventDispatcher');
    }

    /**
     * Example 1: User Login Event
     */
    public function userLoginExample(): void
    {
        echo "=== User Login Event Example ===\n";

        // Simulate user login
        $userData = [
            'id' => 123,
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'roles' => ['user']
        ];

        // Dispatch login event
        $event = new UserLoginEvent($userData, 'password');
        $this->dispatcher->dispatch($event);

        echo "User login event dispatched for user: {$userData['username']}\n";
        echo "Event ID: {$event->getEventId()}\n\n";
    }

    /**
     * Example 2: Custom Event Listener
     */
    public function customListenerExample(): void
    {
        echo "=== Custom Event Listener Example ===\n";

        // Create a custom listener
        $customListener = new class extends AbstractEventListener {
            public function handle(Event $event): void
            {
                if ($event instanceof UserLoginEvent) {
                    echo "Custom listener: Welcome back, {$event->getUser()['username']}!\n";
                    
                    // You could send welcome email, update last login, etc.
                    $this->updateLastLogin($event->getUserId());
                }
            }

            public function shouldHandle(Event $event): bool
            {
                return $event instanceof UserLoginEvent;
            }

            private function updateLastLogin(int $userId): void
            {
                echo "Updated last login time for user ID: $userId\n";
            }
        };

        // Register the listener
        $this->dispatcher->listen('UserLoginEvent', $customListener);

        // Dispatch an event to trigger the listener
        $userData = ['id' => 456, 'username' => 'jane_doe', 'email' => 'jane@example.com'];
        $event = new UserLoginEvent($userData);
        $this->dispatcher->dispatch($event);

        echo "\n";
    }

    /**
     * Example 3: System Events
     */
    public function systemEventsExample(): void
    {
        echo "=== System Events Example ===\n";

        // Database query event
        $queryEvent = new DatabaseQueryEvent(
            'SELECT * FROM users WHERE active = ?',
            [1],
            0.025 // 25ms
        );
        $this->dispatcher->dispatch($queryEvent);
        echo "Database query event dispatched\n";

        // Error event
        try {
            throw new RuntimeException('Something went wrong!');
        } catch (Exception $e) {
            $errorEvent = new ErrorEvent($e, ['context' => 'example']);
            $this->dispatcher->dispatch($errorEvent);
            echo "Error event dispatched\n";
        }

        // HTTP request event
        $requestEvent = new HttpRequestEvent('GET', '/api/users', ['Authorization' => 'Bearer token']);
        $this->dispatcher->dispatch($requestEvent);
        echo "HTTP request event dispatched\n";

        echo "\n";
    }

    /**
     * Example 4: Event Propagation Control
     */
    public function propagationControlExample(): void
    {
        echo "=== Event Propagation Control Example ===\n";

        // Create listeners with different priorities
        $highPriorityListener = new class extends AbstractEventListener {
            public function handle(Event $event): void
            {
                echo "High priority listener executed\n";
                // Stop propagation to prevent other listeners from executing
                $event->stopPropagation();
            }
            public function getPriority(): int { return 200; }
        };

        $lowPriorityListener = new class extends AbstractEventListener {
            public function handle(Event $event): void
            {
                echo "Low priority listener executed (this shouldn't show)\n";
            }
            public function getPriority(): int { return 100; }
        };

        // Register listeners
        $this->dispatcher->listen('TestEvent', $highPriorityListener);
        $this->dispatcher->listen('TestEvent', $lowPriorityListener);

        // Create and dispatch test event
        $testEvent = new class extends Event {
            public function getName(): string { return 'TestEvent'; }
        };

        $this->dispatcher->dispatch($testEvent);
        echo "Propagation was stopped by high priority listener\n\n";
    }

    /**
     * Example 5: Wildcard Listeners
     */
    public function wildcardListenerExample(): void
    {
        echo "=== Wildcard Listener Example ===\n";

        // Create a wildcard listener for all user events
        $userEventLogger = function (Event $event) {
            if (str_contains($event->getName(), 'User')) {
                echo "Wildcard listener caught user event: {$event->getName()}\n";
            }
        };

        // Register wildcard listener
        $this->dispatcher->listen('*User*', $userEventLogger);

        // Dispatch various user events
        $this->dispatcher->dispatch(new UserLoginEvent(['id' => 1, 'username' => 'test']));
        $this->dispatcher->dispatch(new UserLogoutEvent(['id' => 1, 'username' => 'test']));
        $this->dispatcher->dispatch(new UserRegistrationEvent(['id' => 2, 'username' => 'newuser']));

        echo "\n";
    }

    /**
     * Example 6: Queued Events
     */
    public function queuedEventsExample(): void
    {
        echo "=== Queued Events Example ===\n";

        // Create a listener to track queued events
        $queueListener = function (Event $event) {
            echo "Processing queued event: {$event->getName()}\n";
        };

        $this->dispatcher->listen('QueuedEvent', $queueListener);

        // Queue multiple events
        for ($i = 1; $i <= 3; $i++) {
            $event = new class($i) extends Event {
                private int $number;
                public function __construct(int $number) {
                    parent::__construct(['number' => $number]);
                    $this->number = $number;
                }
                public function getName(): string { return 'QueuedEvent'; }
            };
            
            $this->dispatcher->queue($event);
            echo "Queued event #$i\n";
        }

        echo "Processing all queued events...\n";
        $processed = $this->dispatcher->processQueue();
        echo "Processed $processed queued events\n\n";
    }

    /**
     * Example 7: Event Statistics
     */
    public function statisticsExample(): void
    {
        echo "=== Event Statistics Example ===\n";

        // Dispatch some events to generate statistics
        $this->dispatcher->dispatch(new UserLoginEvent(['id' => 1, 'username' => 'user1']));
        $this->dispatcher->dispatch(new UserLoginEvent(['id' => 2, 'username' => 'user2']));
        $this->dispatcher->dispatch(new DatabaseQueryEvent('SELECT 1', [], 0.01));

        // Get statistics
        $stats = $this->dispatcher->getStatistics();
        
        echo "Event Statistics:\n";
        echo "- Total events dispatched: {$stats['total_events']}\n";
        echo "- Registered listeners: {$stats['registered_listeners']}\n";
        echo "- Wildcard listeners: {$stats['wildcard_listeners']}\n";
        echo "- Queued events: {$stats['queued_events']}\n";
        echo "- Currently dispatching: " . ($stats['is_dispatching'] ? 'Yes' : 'No') . "\n";

        if (!empty($stats['event_frequency'])) {
            echo "- Most frequent events:\n";
            foreach ($stats['event_frequency'] as $eventName => $count) {
                echo "  * $eventName: $count times\n";
            }
        }

        echo "\n";
    }

    /**
     * Example 8: Integration with Existing Auth System
     */
    public function authIntegrationExample(): void
    {
        echo "=== Auth System Integration Example ===\n";

        // This shows how you would integrate events into existing Auth class
        $mockAuth = new class {
            private EventDispatcher $dispatcher;

            public function __construct(EventDispatcher $dispatcher)
            {
                $this->dispatcher = $dispatcher;
            }

            public function login(string $username, string $password): bool
            {
                // Simulate authentication logic
                if ($username === 'admin' && $password === 'secret') {
                    $userData = [
                        'id' => 1,
                        'username' => $username,
                        'email' => 'admin@example.com',
                        'roles' => ['admin']
                    ];

                    // Dispatch login event
                    $this->dispatcher->dispatch(new UserLoginEvent($userData));
                    
                    echo "Admin logged in successfully\n";
                    return true;
                }

                echo "Login failed\n";
                return false;
            }

            public function logout(array $userData): void
            {
                // Dispatch logout event
                $this->dispatcher->dispatch(new UserLogoutEvent($userData));
                echo "User logged out\n";
            }
        };

        $auth = $mockAuth($this->dispatcher);
        $auth->login('admin', 'secret');
        $auth->logout(['id' => 1, 'username' => 'admin']);

        echo "\n";
    }

    /**
     * Run all examples
     */
    public function runAllExamples(): void
    {
        echo "🎯 Event System Examples\n";
        echo str_repeat("=", 50) . "\n\n";

        $this->userLoginExample();
        $this->customListenerExample();
        $this->systemEventsExample();
        $this->propagationControlExample();
        $this->wildcardListenerExample();
        $this->queuedEventsExample();
        $this->statisticsExample();
        $this->authIntegrationExample();

        echo "✅ All event examples completed!\n";
    }
}

// Run examples if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $examples = new EventExamples();
    $examples->runAllExamples();
}