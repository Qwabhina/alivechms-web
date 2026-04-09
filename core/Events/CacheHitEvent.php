<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

use Exception;
use Error;
use RuntimeException;
use LogicException;

/**
 * Cache Hit Event
 */
class CacheHitEvent extends Event
{
   public function __construct(string $key, $value, string $store = 'default')
   {
      parent::__construct([
         'key' => $key,
         'value' => $value,
         'store' => $store,
         'hit' => true
      ]);
   }

   public function getKey(): string
   {
      return $this->getData('key');
   }

   public function getValue()
   {
      return $this->getData('value');
   }
}
