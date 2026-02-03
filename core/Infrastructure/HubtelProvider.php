<?php
declare(strict_types=1);

namespace AliveChMS\Core\Infrastructure;

use AliveChMS\Core\System\Helpers;

/**
 * Hubtel SMS Provider.
 */
class HubtelProvider extends BaseSmsProvider
{
   /** @var string Hubtel API URL */
   private string $url = 'https://smsc.hubtel.com/v1/messages/send';

   /**
    * @inheritdoc
    */
   public function send(string $to, string $message): bool
   {
      $to = $this->normalizePhone($to);
      if (strlen($to) !== 12 || !str_starts_with($to, '233')) {
         $this->lastError = "Invalid Ghana phone number: $to";
         return false;
      }

      $payload = [
         'from' => $_ENV['HUBTEL_SENDER'] ?? 'AliveChMS',
         'to' => $to,
         'content' => $message,
         'clientid' => $_ENV['HUBTEL_CLIENT_ID'] ?? '',
         'clientsecret' => $_ENV['HUBTEL_CLIENT_SECRET'] ?? ''
      ];

      return $this->makeRequest($payload);
   }

   /**
    * Make API request to Hubtel.
    *
    * @param array $payload
    * @return bool
    */
   private function makeRequest(array $payload): bool
   {
      $ch = curl_init($this->url);
      curl_setopt_array($ch, [
         CURLOPT_POST => true,
         CURLOPT_POSTFIELDS => http_build_query($payload),
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_TIMEOUT => 15,
         CURLOPT_SSL_VERIFYPEER => true,
         CURLOPT_FOLLOWLOCATION => true,
      ]);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $error = curl_error($ch);
      $ch = null;

      if ($httpCode >= 200 && $httpCode < 300 && $response !== false) {
         return true;
      }

      $this->lastError = "Hubtel failed | Code: $httpCode | Error: $error | Response: " . substr($response ?: '', 0, 200);
      return false;
   }
}
