<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

/**
 * User-related Events
 *
 * Events related to user actions and lifecycle in the system.
 *
 * @package  AliveChMS\Core\Events
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */



/**
 * User Login Event
 */
class UserLoginEvent extends Event
{
   public function __construct(array $userData, string $loginMethod = 'password')
   {
      parent::__construct([
         'user' => $userData,
         'login_method' => $loginMethod,
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

   public function getLoginMethod(): string
   {
      return $this->getData('login_method');
   }

   public function getIpAddress(): string
   {
      return $this->getData('ip_address');
   }
}
