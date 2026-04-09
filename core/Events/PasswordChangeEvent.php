<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

/**
 * Password Change Event
 */
class PasswordChangeEvent extends Event
{
   public function __construct(int $userId, bool $forced = false)
   {
      parent::__construct([
         'user_id' => $userId,
         'forced' => $forced,
         'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
      ]);
   }

   public function getUserId(): int
   {
      return $this->getData('user_id');
   }

   public function wasForced(): bool
   {
      return $this->getData('forced');
   }
}
