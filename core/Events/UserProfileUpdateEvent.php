<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

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
