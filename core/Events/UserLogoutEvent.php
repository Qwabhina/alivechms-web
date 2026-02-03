<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

/**
 * User Logout Event
 */
class UserLogoutEvent extends Event
{
   public function __construct(array $userData, string $reason = 'manual')
   {
      parent::__construct([
         'user' => $userData,
         'reason' => $reason,
         'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
      ]);
   }

   public function getUser(): array
   {
      return $this->getData('user');
   }

   public function getUserId(): int
   {
      return (int)($this->getData('user')['id'] ?? 0);
   }

   public function getReason(): string
   {
      return $this->getData('reason');
   }
}
