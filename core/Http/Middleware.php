<?php

declare(strict_types=1);

namespace AliveChMS\Core\Http;

/**
 * Base Middleware Interface/Class
 */
abstract class Middleware
{
   protected int $priority = 100;

   abstract public function execute(Request $request, callable $next): Response;

   public function getPriority(): int
   {
      return $this->priority;
   }

   public function getName(): string
   {
      return static::class;
   }
}
