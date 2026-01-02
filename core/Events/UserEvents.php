<?php

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

declare(strict_types=1);

require_once __DIR__ . '/Event.php';

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

/**
 * User Profile Update Event
 */
class UserProfileUpdateEvent extends Event
{
   public function __construct(array $userData, array $changes)
   {
      parent::__construct([
         'user' => $userData,
         'changes' => $changes,
         'updated_by' => $userData['id'] ?? null
      ]);
   }

   public function getUser(): array
   {
      return $this->getData('user');
   }

   public function getChanges(): array
   {
      return $this->getData('changes');
   }

   public function hasChanged(string $field): bool
   {
      return array_key_exists($field, $this->getData('changes'));
   }
}

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
