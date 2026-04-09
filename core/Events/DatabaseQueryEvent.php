<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

use Exception;
use Error;
use RuntimeException;
use LogicException;

/**
 * Database Query Event
 */
class DatabaseQueryEvent extends Event
{
   public function __construct(string $query, array $bindings = [], float $duration = 0.0)
   {
      parent::__construct([
         'query' => $query,
         'bindings' => $bindings,
         'duration' => $duration,
         'connection' => 'default'
      ]);
   }

   public function getQuery(): string
   {
      return $this->getData('query');
   }

   public function getBindings(): array
   {
      return $this->getData('bindings');
   }

   public function getDuration(): float
   {
      return $this->getData('duration');
   }

   public function isSlow(float $threshold = 1.0): bool
   {
      return $this->getDuration() > $threshold;
   }
}
