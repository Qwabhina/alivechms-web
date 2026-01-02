<?php

/**
 * Event System Tests
 *
 * Comprehensive tests for the event system including events,
 * listeners, dispatcher, and integration.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../core/Events/Event.php';
require_once __DIR__ . '/../../core/Events/EventListener.php';
require_once __DIR__ . '/../../core/Events/EventDispatcher.php';
require_once __DIR__ . '/../../core/Events/UserEvents.php';
require_once __DIR__ . '/../../core/Events/SystemEvents.php';

use PHPUnit\Framework\TestCase;

class EventSystemTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testEventCreation()
    {
        $event = new class(['test' => 'data']) extends Event {
            public function getName(): string { return 'TestEvent'; }
        };

        $this->assertEquals('TestEvent', $event->getName());
        $this->assertEquals('data', $event->getData('test'));
        $this->assertIsFloat($event->getTimestamp());
        $this->assertIsString($event->getEventId());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testEventDataManipulation()
    {
        $event = new class extends Event {
            public function getName(): string { return 'TestEvent'; }
        };

        $event->setData('key1', 'value1');
        $event->setData('key2', 'value2');

        $this->assertTrue($event->hasData('key1'));
        $this->assertEquals('value1', $event->getData('key1'));
        $this->assertFalse($event->hasData('nonexistent'));
        $this->assertNull($event->getData('nonexistent'));

        $allData = $event->getData();
        $this->assertIsArray($allData);
        $this->assertArrayHasKey('key1', $allData);
        $this->assertArrayHasKey('key2', $allData);
    }

    public function testEventPropagationControl()
    {
        $event = new class extends Event {
            public function getName(): string { return 'TestEvent'; }
        };

        $this->assertFalse($event->isPropagationStopped());
        
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testEventSerialization()
    {
        $event = new class(['test' => 'data']) extends Event {
            public function getName(): string { return 'TestEvent'; }
        };

        $array = $event->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('TestEvent', $array['name']);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('data', $array);

        $json = $event->toJson();
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals($array, $decoded);
    }

    public function testEventListenerInterface()
    {
        $listener = new class extends AbstractEventListener {
            public $handled = false;
            
            public function handle(Event $event): void
            {
                $this->handled = true;
            }
        };

        $this->assertEquals(100, $listener->getPriority());
        $this->assertTrue($listener->shouldHandle(new class extends Event {
            public function getName(): string { return 'TestEvent'; }
        }));
        $this->assertIsString($listener->getName());
    }

    public function testEventDispatcherBasicFunctionality()
    {
        $executed = false;
        
        $listener = function (Event $event) use (&$executed) {
            $executed = true;
        };

        $this->dispatcher->listen('TestEvent', $listener);
        
        $event = new class extends Event {
            public function getName(): string { return 'TestEvent'; }
        };

        $dispatchedEvent = $this->dispatcher->dispatch($event);
        
        $this->assertTrue($executed);
        $this->assertSame($event, $dispatchedEvent);
    }

    public function testEventDispatcherWithStringEvent()
    {
        $executed = false;
        
        $this->dispatcher->listen('StringEvent', function (Event $event) use (&$executed) {
            $executed = true;
            $this->assertEquals('test_data', $event->getData('test'));
        });

        $this->dispatcher->dispatch('StringEvent', ['test' => 'test_data']);
        $this->assertTrue($executed);
    }

    public function testListenerPriority()
    {
        $executionOrder = [];

        $highPriorityListener = new class($executionOrder) extends AbstractEventListener {
            private array $order;
            public function __construct(array &$order) { $this->order = &$order; }
            public function handle(Event $event): void { $this->order[] = 'high'; }
            public function getPriority(): int { return 200; }
        };

        $lowPriorityListener = new class($executionOrder) extends AbstractEventListener {
            private array $order;
            public function __construct(array &$order) { $this->order = &$order; }
            public function handle(Event $event): void { $this->order[] = 'low'; }
            public function getPriority(): int { return 100; }
        };

        $this->dispatcher->listen('PriorityTest', $lowPriorityListener);
        $this->dispatcher->listen('PriorityTest', $highPriorityListener);

        $event = new class extends Event {
            public function getName(): string { return 'PriorityTest'; }
        };

        $this->dispatcher->dispatch($event);
        $this->assertEquals(['high', 'low'], $executionOrder);
    }

    public function testEventPropagationStopping()
    {
        $executionOrder = [];

        $stoppingListener = new class($executionOrder) extends AbstractEventListener {
            private array $order;
            public function __construct(array &$order) { $this->order = &$order; }
            public function handle(Event $event): void {
                $this->order[] = 'stopping';
                $event->stopPropagation();
            }
            public function getPriority(): int { return 200; }
        };

        $normalListener = new class($executionOrder) extends AbstractEventListener {
            private array $order;
            public function __construct(array &$order) { $this->order = &$order; }
            public function handle(Event $event): void { $this->order[] = 'normal'; }
            public function getPriority(): int { return 100; }
        };

        $this->dispatcher->listen('PropagationTest', $stoppingListener);
        $this->dispatcher->listen('PropagationTest', $normalListener);

        $event = new class extends Event {
            public function getName(): string { return 'PropagationTest'; }
        };

        $this->dispatcher->dispatch($event);
        $this->assertEquals(['stopping'], $executionOrder);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testWildcardListeners()
    {
        $wildcardExecuted = false;
        $specificExecuted = false;

        $this->dispatcher->listen('User*', function (Event $event) use (&$wildcardExecuted) {
            $wildcardExecuted = true;
        });

        $this->dispatcher->listen('UserLoginEvent', function (Event $event) use (&$specificExecuted) {
            $specificExecuted = true;
        });

        $event = new UserLoginEvent(['id' => 1, 'username' => 'test']);
        $this->dispatcher->dispatch($event);

        $this->assertTrue($wildcardExecuted);
        $this->assertTrue($specificExecuted);
    }

    public function testEventQueue()
    {
        $executed = [];

        $this->dispatcher->listen('QueuedEvent', function (Event $event) use (&$executed) {
            $executed[] = $event->getData('number');
        });

        // Queue events
        for ($i = 1; $i <= 3; $i++) {
            $event = new class($i) extends Event {
                public function __construct(int $number) {
                    parent::__construct(['number' => $number]);
                }
                public function getName(): string { return 'QueuedEvent'; }
            };
            $this->dispatcher->queue($event);
        }

        $this->assertEmpty($executed); // Should not be executed yet

        $processed = $this->dispatcher->processQueue();
        $this->assertEquals(3, $processed);
        $this->assertEquals([1, 2, 3], $executed);
    }

    public function testListenerManagement()
    {
        $executed = false;
        
        $listener = function (Event $event) use (&$executed) {
            $executed = true;
        };

        // Test listener registration
        $this->assertFalse($this->dispatcher->hasListeners('TestEvent'));
        
        $this->dispatcher->listen('TestEvent', $listener);
        $this->assertTrue($this->dispatcher->hasListeners('TestEvent'));

        // Test listener removal
        $this->dispatcher->forget('TestEvent', $listener);
        $this->assertFalse($this->dispatcher->hasListeners('TestEvent'));

        // Test removing all listeners for event
        $this->dispatcher->listen('TestEvent', $listener);
        $this->dispatcher->listen('TestEvent', function () {});
        $this->assertTrue($this->dispatcher->hasListeners('TestEvent'));
        
        $this->dispatcher->forget('TestEvent');
        $this->assertFalse($this->dispatcher->hasListeners('TestEvent'));
    }

    public function testUserEvents()
    {
        $userData = ['id' => 1, 'username' => 'testuser', 'email' => 'test@example.com'];

        // Test UserLoginEvent
        $loginEvent = new UserLoginEvent($userData, 'password');
        $this->assertEquals($userData, $loginEvent->getUser());
        $this->assertEquals(1, $loginEvent->getUserId());
        $this->assertEquals('password', $loginEvent->getLoginMethod());
        $this->assertIsString($loginEvent->getIpAddress());

        // Test UserLogoutEvent
        $logoutEvent = new UserLogoutEvent($userData, 'manual');
        $this->assertEquals($userData, $logoutEvent->getUser());
        $this->assertEquals(1, $logoutEvent->getUserId());
        $this->assertEquals('manual', $logoutEvent->getReason());

        // Test UserRegistrationEvent
        $registrationEvent = new UserRegistrationEvent($userData);
        $this->assertEquals($userData, $registrationEvent->getUser());
        $this->assertEquals(1, $registrationEvent->getUserId());
        $this->assertEquals('test@example.com', $registrationEvent->getEmail());

        // Test PasswordChangeEvent
        $passwordEvent = new PasswordChangeEvent(1, true);
        $this->assertEquals(1, $passwordEvent->getUserId());
        $this->assertTrue($passwordEvent->wasForced());
    }

    public function testSystemEvents()
    {
        // Test DatabaseQueryEvent
        $queryEvent = new DatabaseQueryEvent('SELECT * FROM users', ['active' => 1], 0.5);
        $this->assertEquals('SELECT * FROM users', $queryEvent->getQuery());
        $this->assertEquals(['active' => 1], $queryEvent->getBindings());
        $this->assertEquals(0.5, $queryEvent->getDuration());
        $this->assertFalse($queryEvent->isSlow(1.0));
        $this->assertTrue($queryEvent->isSlow(0.1));

        // Test ErrorEvent
        $exception = new RuntimeException('Test error');
        $errorEvent = new ErrorEvent($exception, ['context' => 'test']);
        $this->assertSame($exception, $errorEvent->getException());
        $this->assertEquals('Test error', $errorEvent->getMessage());
        $this->assertEquals(['context' => 'test'], $errorEvent->getContext());
        $this->assertIsString($errorEvent->getSeverity());

        // Test HttpRequestEvent
        $requestEvent = new HttpRequestEvent('GET', '/api/users', ['Authorization' => 'Bearer token']);
        $this->assertEquals('GET', $requestEvent->getMethod());
        $this->assertEquals('/api/users', $requestEvent->getUri());
        $this->assertTrue($requestEvent->isApiRequest());

        // Test HttpResponseEvent
        $responseEvent = new HttpResponseEvent(200, ['Content-Type' => 'application/json'], 0.1);
        $this->assertEquals(200, $responseEvent->getStatusCode());
        $this->assertEquals(0.1, $responseEvent->getDuration());
        $this->assertTrue($responseEvent->isSuccessful());
        $this->assertFalse($responseEvent->isError());
    }

    public function testEventStatistics()
    {
        // Dispatch some events
        $this->dispatcher->dispatch('TestEvent1');
        $this->dispatcher->dispatch('TestEvent1');
        $this->dispatcher->dispatch('TestEvent2');

        $stats = $this->dispatcher->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_events', $stats);
        $this->assertArrayHasKey('registered_listeners', $stats);
        $this->assertArrayHasKey('event_frequency', $stats);
        
        $this->assertEquals(3, $stats['total_events']);
        $this->assertArrayHasKey('TestEvent1', $stats['event_frequency']);
        $this->assertEquals(2, $stats['event_frequency']['TestEvent1']);
    }

    public function testDispatchHistory()
    {
        $this->dispatcher->dispatch('HistoryTest1');
        $this->dispatcher->dispatch('HistoryTest2');

        $history = $this->dispatcher->getDispatchHistory();
        $this->assertCount(2, $history);
        
        $this->assertEquals('HistoryTest1', $history[0]['event_name']);
        $this->assertEquals('HistoryTest2', $history[1]['event_name']);
        
        $this->dispatcher->clearHistory();
        $history = $this->dispatcher->getDispatchHistory();
        $this->assertEmpty($history);
    }

    public function testStaticHelperMethods()
    {
        $executed = false;
        
        EventDispatcher::on('StaticTest', function (Event $event) use (&$executed) {
            $executed = true;
        });

        EventDispatcher::fire('StaticTest');
        $this->assertTrue($executed);

        EventDispatcher::off('StaticTest');
        $this->assertFalse(EventDispatcher::getInstance()->hasListeners('StaticTest'));
    }

    public function testConditionalListenerExecution()
    {
        $conditionalListener = new class extends AbstractEventListener {
            public $executed = false;
            
            public function handle(Event $event): void
            {
                $this->executed = true;
            }
            
            public function shouldHandle(Event $event): bool
            {
                return $event->getData('should_handle') === true;
            }
        };

        $this->dispatcher->listen('ConditionalTest', $conditionalListener);

        // Should not execute
        $event1 = new class(['should_handle' => false]) extends Event {
            public function getName(): string { return 'ConditionalTest'; }
        };
        $this->dispatcher->dispatch($event1);
        $this->assertFalse($conditionalListener->executed);

        // Should execute
        $event2 = new class(['should_handle' => true]) extends Event {
            public function getName(): string { return 'ConditionalTest'; }
        };
        $this->dispatcher->dispatch($event2);
        $this->assertTrue($conditionalListener->executed);
    }
}