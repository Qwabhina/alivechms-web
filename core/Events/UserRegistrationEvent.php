<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

/**
 * User Registration Event
 */
class UserRegistrationEvent extends Event
{
   public function __construct(array $userData)
   {
      parent::__construct([
         'user' => $userData,
         'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
         'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
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

   public function getEmail(): string
   {
      return $this->getData('user')['email'] ?? '';
   }
}
