<?php

/**
 * Base Event Class
 *
 * Base class for all events in the system. Events represent something
 * that has happened in the application and can be listened to by
 * event listeners.
 *
 * Features:
 * - Event data encapsulation
 * - Propagation control
 * - Timestamp tracking
 * - Event metadata
 *
 * @package  AliveChMS\Core\Events
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

abstract class Event
{
   protected array $data = [];
   protected bool $propagationStopped = false;
   protected float $timestamp;
   protected string $eventId;

   public function __construct(array $data = [])
   {
      $this->data = $data;
      $this->timestamp = microtime(true);
      $this->eventId = uniqid('event_', true);
   }

   /**
    * Get event name
    *
    * @return string Event name
    */
   public function getName(): string
   {
      return static::class;
   }

   /**
    * Get event data
    *
    * @param string|null $key Specific data key or null for all data
    * @return mixed Event data
    */
   public function getData(?string $key = null)
   {
      if ($key === null) {
         return $this->data;
      }
      return $this->data[$key] ?? null;
   }

   /**
    * Set event data
    *
    * @param string $key Data key
    * @param mixed $value Data value
    * @return self
    */
   public function setData(string $key, $value): self
   {
      $this->data[$key] = $value;
      return $this;
   }

   /**
    * Check if event has specific data
    *
    * @param string $key Data key
    * @return bool
    */
   public function hasData(string $key): bool
   {
      return array_key_exists($key, $this->data);
   }

   /**
    * Stop event propagation
    *
    * @return self
    */
   public function stopPropagation(): self
   {
      $this->propagationStopped = true;
      return $this;
   }

   /**
    * Check if propagation is stopped
    *
    * @return bool
    */
   public function isPropagationStopped(): bool
   {
      return $this->propagationStopped;
   }

   /**
    * Get event timestamp
    *
    * @return float Unix timestamp with microseconds
    */
   public function getTimestamp(): float
   {
      return $this->timestamp;
   }

   /**
    * Get event ID
    *
    * @return string Unique event identifier
    */
   public function getEventId(): string
   {
      return $this->eventId;
   }

   /**
    * Get event as array
    *
    * @return array Event representation
    */
   public function toArray(): array
   {
      return [
         'name' => $this->getName(),
         'id' => $this->eventId,
         'timestamp' => $this->timestamp,
         'data' => $this->data,
         'propagation_stopped' => $this->propagationStopped
      ];
   }

   /**
    * Convert event to JSON
    *
    * @return string JSON representation
    */
   public function toJson(): string
   {
      return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
   }

   /**
    * Magic method for string conversion
    *
    * @return string Event name
    */
   public function __toString(): string
   {
      return $this->getName();
   }
}
