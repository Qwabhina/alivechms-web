<?php

/**
 * Event Dispatcher
 *
 * Central event dispatcher that manages event listeners and dispatches
 * events to registered listeners. Supports priority-based execution,
 * wildcard listeners, and event propagation control.
 *
 * Features:
 * - Event listener registration and management
 * - Priority-based listener execution
 * - Wildcard event listening
 * - Event propagation control
 * - Performance monitoring
 * - Async event dispatching (queued)
 *
 * @package  AliveChMS\Core\Events
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Event.php';
require_once __DIR__ . '/EventListener.php';

class EventDispatcher
{
   private static ?self $instance = null;
   private array $listeners = [];
   private array $wildcardListeners = [];
   private array $eventQueue = [];
   private bool $dispatching = false;
   private array $dispatchHistory = [];
   private int $maxHistorySize = 1000;

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
    * Register an event listener
    *
    * @param string $eventName Event name or wildcard pattern
    * @param EventListener|callable $listener Event listener
    * @return self
    */
   public function listen(string $eventName, $listener): self
   {
      if (!($listener instanceof EventListener) && !is_callable($listener)) {
         throw new InvalidArgumentException('Listener must be EventListener instance or callable');
      }

      // Handle wildcard listeners
      if (str_contains($eventName, '*')) {
         $this->wildcardListeners[$eventName][] = $listener;
      } else {
         $this->listeners[$eventName][] = $listener;
      }

      // Sort listeners by priority
      $this->sortListeners($eventName);

      return $this;
   }

   /**
    * Register multiple listeners at once
    *
    * @param array $listeners Array of event => listener mappings
    * @return self
    */
   public function listenMany(array $listeners): self
   {
      foreach ($listeners as $eventName => $listener) {
         if (is_array($listener)) {
            foreach ($listener as $singleListener) {
               $this->listen($eventName, $singleListener);
            }
         } else {
            $this->listen($eventName, $listener);
         }
      }
      return $this;
   }

   /**
    * Remove event listener
    *
    * @param string $eventName Event name
    * @param EventListener|callable|null $listener Specific listener or null for all
    * @return self
    */
   public function forget(string $eventName, $listener = null): self
   {
      if ($listener === null) {
         // Remove all listeners for event
         unset($this->listeners[$eventName]);

         // Remove wildcard listeners
         foreach ($this->wildcardListeners as $pattern => $listeners) {
            if ($pattern === $eventName) {
               unset($this->wildcardListeners[$pattern]);
            }
         }
      } else {
         // Remove specific listener
         if (isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = array_filter(
               $this->listeners[$eventName],
               function ($l) use ($listener) {
                  return $l !== $listener;
               }
            );
         }
      }

      return $this;
   }

   /**
    * Dispatch an event
    *
    * @param Event|string $event Event instance or event name
    * @param array $data Event data (if event name provided)
    * @return Event The dispatched event
    */
   public function dispatch($event, array $data = []): Event
   {
      // Create event instance if string provided
      if (is_string($event)) {
         $event = new class($data) extends Event {
            private string $name;

            public function __construct(array $data = [], string $name = '')
            {
               parent::__construct($data);
               $this->name = $name;
            }

            public function getName(): string
            {
               return $this->name ?: parent::getName();
            }
         };
         $event->setData('event_name', $event);
      }

      $startTime = microtime(true);
      $this->dispatching = true;

      try {
         $listeners = $this->getListenersForEvent($event);
         $executedListeners = [];

         foreach ($listeners as $listener) {
            if ($event->isPropagationStopped()) {
               break;
            }

            if ($this->shouldExecuteListener($listener, $event)) {
               $this->executeListener($listener, $event);
               $executedListeners[] = $this->getListenerName($listener);
            }
         }

         // Record dispatch history
         $this->recordDispatch($event, $executedListeners, microtime(true) - $startTime);
      } finally {
         $this->dispatching = false;
      }

      return $event;
   }

   /**
    * Queue event for async dispatch
    *
    * @param Event|string $event Event instance or event name
    * @param array $data Event data (if event name provided)
    * @return self
    */
   public function queue($event, array $data = []): self
   {
      if (is_string($event)) {
         $event = new class($data) extends Event {
            private string $name;

            public function __construct(array $data = [], string $name = '')
            {
               parent::__construct($data);
               $this->name = $name;
            }

            public function getName(): string
            {
               return $this->name ?: parent::getName();
            }
         };
      }

      $this->eventQueue[] = $event;
      return $this;
   }

   /**
    * Process queued events
    *
    * @return int Number of events processed
    */
   public function processQueue(): int
   {
      $processed = 0;

      while (!empty($this->eventQueue)) {
         $event = array_shift($this->eventQueue);
         $this->dispatch($event);
         $processed++;
      }

      return $processed;
   }

   /**
    * Get listeners for specific event
    *
    * @param string $eventName Event name
    * @return array Array of listeners
    */
   public function getListeners(string $eventName): array
   {
      $listeners = $this->listeners[$eventName] ?? [];

      // Add wildcard listeners
      foreach ($this->wildcardListeners as $pattern => $wildcardListeners) {
         if ($this->matchesWildcard($eventName, $pattern)) {
            $listeners = array_merge($listeners, $wildcardListeners);
         }
      }

      return $listeners;
   }

   /**
    * Check if event has listeners
    *
    * @param string $eventName Event name
    * @return bool
    */
   public function hasListeners(string $eventName): bool
   {
      return !empty($this->getListeners($eventName));
   }

   /**
    * Get all registered events
    *
    * @return array Array of event names
    */
   public function getEvents(): array
   {
      return array_keys($this->listeners);
   }

   /**
    * Get dispatch history
    *
    * @param int $limit Maximum number of entries
    * @return array Dispatch history
    */
   public function getDispatchHistory(int $limit = 100): array
   {
      return array_slice($this->dispatchHistory, -$limit);
   }

   /**
    * Clear dispatch history
    *
    * @return self
    */
   public function clearHistory(): self
   {
      $this->dispatchHistory = [];
      return $this;
   }

   /**
    * Get event statistics
    *
    * @return array Event statistics
    */
   public function getStatistics(): array
   {
      $stats = [
         'total_events' => count($this->dispatchHistory),
         'registered_listeners' => array_sum(array_map('count', $this->listeners)),
         'wildcard_listeners' => array_sum(array_map('count', $this->wildcardListeners)),
         'queued_events' => count($this->eventQueue),
         'is_dispatching' => $this->dispatching
      ];

      // Event frequency
      $eventCounts = [];
      foreach ($this->dispatchHistory as $record) {
         $eventName = $record['event_name'];
         $eventCounts[$eventName] = ($eventCounts[$eventName] ?? 0) + 1;
      }
      arsort($eventCounts);
      $stats['event_frequency'] = array_slice($eventCounts, 0, 10);

      return $stats;
   }

   /**
    * Get listeners for event instance
    */
   private function getListenersForEvent(Event $event): array
   {
      return $this->getListeners($event->getName());
   }

   /**
    * Sort listeners by priority
    */
   private function sortListeners(string $eventName): void
   {
      if (isset($this->listeners[$eventName])) {
         usort($this->listeners[$eventName], function ($a, $b) {
            $priorityA = $this->getListenerPriority($a);
            $priorityB = $this->getListenerPriority($b);
            return $priorityB <=> $priorityA; // Higher priority first
         });
      }

      // Sort wildcard listeners
      foreach ($this->wildcardListeners as $pattern => &$listeners) {
         usort($listeners, function ($a, $b) {
            $priorityA = $this->getListenerPriority($a);
            $priorityB = $this->getListenerPriority($b);
            return $priorityB <=> $priorityA;
         });
      }
   }

   /**
    * Get listener priority
    */
   private function getListenerPriority($listener): int
   {
      if ($listener instanceof EventListener) {
         return $listener->getPriority();
      }
      return 100; // Default priority for callable listeners
   }

   /**
    * Check if listener should execute
    */
   private function shouldExecuteListener($listener, Event $event): bool
   {
      if ($listener instanceof EventListener) {
         return $listener->shouldHandle($event);
      }
      return true; // Callable listeners always execute
   }

   /**
    * Execute listener
    */
   private function executeListener($listener, Event $event): void
   {
      try {
         if ($listener instanceof EventListener) {
            $listener->handle($event);
         } elseif (is_callable($listener)) {
            $listener($event);
         }
      } catch (Exception $e) {
         // Log error but don't stop event propagation
         error_log("Event listener error: " . $e->getMessage());
      }
   }

   /**
    * Get listener name for debugging
    */
   private function getListenerName($listener): string
   {
      if ($listener instanceof EventListener) {
         return $listener->getName();
      } elseif (is_callable($listener)) {
         if (is_array($listener)) {
            return (is_object($listener[0]) ? get_class($listener[0]) : $listener[0]) . '::' . $listener[1];
         } elseif (is_object($listener)) {
            return get_class($listener) . '::__invoke';
         }
         return 'callable';
      }
      return 'unknown';
   }

   /**
    * Record dispatch in history
    */
   private function recordDispatch(Event $event, array $executedListeners, float $duration): void
   {
      $this->dispatchHistory[] = [
         'event_name' => $event->getName(),
         'event_id' => $event->getEventId(),
         'timestamp' => $event->getTimestamp(),
         'duration' => $duration,
         'listeners_executed' => $executedListeners,
         'propagation_stopped' => $event->isPropagationStopped()
      ];

      // Limit history size
      if (count($this->dispatchHistory) > $this->maxHistorySize) {
         $this->dispatchHistory = array_slice($this->dispatchHistory, -$this->maxHistorySize);
      }
   }

   /**
    * Check if event name matches wildcard pattern
    */
   private function matchesWildcard(string $eventName, string $pattern): bool
   {
      // Escape dots first, then replace asterisks
      $regex = str_replace('.', '\.', $pattern);
      $regex = str_replace('*', '.*', $regex);
      return preg_match('/^' . $regex . '$/', $eventName) === 1;
   }
   public static function fire($event, array $data = []): Event
   {
      return self::getInstance()->dispatch($event, $data);
   }

   public static function on(string $eventName, $listener): void
   {
      self::getInstance()->listen($eventName, $listener);
   }

   public static function off(string $eventName, $listener = null): void
   {
      self::getInstance()->forget($eventName, $listener);
   }
}
