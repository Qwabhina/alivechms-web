<?php
declare(strict_types=1);

namespace AliveChMS\Core\Infrastructure;

use AliveChMS\Core\System\Helpers;

/**
 * SMS Gateway Controller.
 */
class SMSGateway
{
   /** @var SmsProviderInterface|null Cached provider instance */
   private static ?SmsProviderInterface $provider = null;

   /**
    * Resolve the provider based on ENV configuration.
    *
    * @return SmsProviderInterface
    */
   private static function getProvider(): SmsProviderInterface
   {
      if (self::$provider !== null) {
         return self::$provider;
      }

      $providerName = strtoupper($_ENV['SMS_PROVIDER'] ?? 'HUBTEL');

      return match ($providerName) {
         'HUBTEL' => self::$provider = new HubtelProvider(),
         'TEXTME' => self::$provider = new TextMeProvider(),
         'GENERIC' => self::$provider = new GenericHttpProvider(),
         default => self::$provider = new HubtelProvider(),
      };
   }

   /**
    * Send SMS using the configured provider.
    *
    * @param string $phone Recipient phone number.
    * @param string $message Message content.
    * @return bool True on success.
    */
   public static function send(string $phone, string $message): bool
   {
      $provider = self::getProvider();
      $success = $provider->send($phone, $message);

      if (!$success) {
         Helpers::logError(
            "SMS delivery failed | Provider: " . get_class($provider) .
            " | Error: " . $provider->getLastError()
         );
      }

      return $success;
   }
}
