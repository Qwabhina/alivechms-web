<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

use Exception;
use Error;
use RuntimeException;
use LogicException;

/**
 * Cache Miss Event
 */
class CacheMissEvent extends Event
{
   public function __construct(string $key, string $store = 'default')
   {
      parent::__construct([
         'key' => $key,
         'store' => $store,
         'hit' => false
      ]);
   }

   public function getKey(): string
   {
      return $this->getData('key');
   }
}
