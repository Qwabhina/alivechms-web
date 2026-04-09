<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

use Exception;
use Error;
use RuntimeException;
use LogicException;

/**
 * HTTP Response Event
 */
class HttpResponseEvent extends Event
{
   public function __construct(int $statusCode, array $headers = [], float $duration = 0.0)
   {
      parent::__construct([
         'status_code' => $statusCode,
         'headers' => $headers,
         'duration' => $duration,
         'memory_usage' => memory_get_usage(true),
         'peak_memory' => memory_get_peak_usage(true)
      ]);
   }

   public function getStatusCode(): int
   {
      return $this->getData('status_code');
   }

   public function getDuration(): float
   {
      return $this->getData('duration');
   }

   public function isSuccessful(): bool
   {
      return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
   }

   public function isError(): bool
   {
      return $this->getStatusCode() >= 400;
   }
}
