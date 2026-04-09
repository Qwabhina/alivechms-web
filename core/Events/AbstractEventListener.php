<?php
/**
 * Abstract Event Listener
 *
 * Base implementation of EventListener interface with common functionality
 *
 * @package  AliveChMS\Core\Events
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

namespace AliveChMS\Core\Events;

abstract class AbstractEventListener implements EventListener
{
   /**
    * Handle the event
    *
    * @param Event $event The event to handle
    * @return void
    */
   abstract public function handle(Event $event): void;

   /**
    * Get listener priority (higher numbers execute first)
    *
    * @return int Priority value
    */
   public function getPriority(): int
   {
      return 100;
   }

   /**
    * Check if listener should handle this event
    *
    * @param Event $event The event to check
    * @return bool Whether to handle the event
    */
   public function shouldHandle(Event $event): bool
   {
      return true;
   }

   /**
    * Get listener name for debugging
    *
    * @return string Listener name
    */
   public function getName(): string
   {
      return static::class;
   }
}
