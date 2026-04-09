<?php
declare(strict_types=1);

namespace AliveChMS\Core\Infrastructure;

use AliveChMS\Core\System\Helpers;

/**
 * Base class for common provider functionality.
 */
abstract class BaseSmsProvider implements SmsProviderInterface
{
   /** @var string|null $lastError Holds last provider error. */
   protected ?string $lastError = null;

   /**
    * @inheritdoc
    */
   public function getLastError(): ?string
   {
      return $this->lastError;
   }

   /**
    * Normalize a Ghanaian phone number to international format.
    *
    * @param string $phone
    * @return string
    */
   protected function normalizePhone(string $phone): string
   {
      $phone = preg_replace('/\D/', '', $phone);
      if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
         $phone = '233' . substr($phone, 1);
      }
      return $phone;
   }
}
