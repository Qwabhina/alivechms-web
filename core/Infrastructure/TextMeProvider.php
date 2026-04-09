<?php
declare(strict_types=1);

namespace AliveChMS\Core\Infrastructure;

use AliveChMS\Core\System\Helpers;

/**
 * TextMe Ghana SMS Provider.
 */
class TextMeProvider extends BaseSmsProvider
{
   /** @var string TextMe API URL */
   private string $url = 'https://api.textme.com.gh/sms/send';

   /**
    * @inheritdoc
    */
   public function send(string $to, string $message): bool
   {
      $to = $this->normalizePhone($to);

      $payload = [
         'to' => $to,
         'message' => $message,
         'sender_id' => $_ENV['TEXTME_SENDER'] ?? 'AliveChMS',
         'api_key' => $_ENV['TEXTME_API_KEY'] ?? ''
      ];

      $ch = curl_init($this->url);
      curl_setopt_array($ch, [
         CURLOPT_POST => true,
         CURLOPT_POSTFIELDS => http_build_query($payload),
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_TIMEOUT => 15,
         CURLOPT_SSL_VERIFYPEER => true,
      ]);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $ch = null;

      $success = $httpCode === 200;

      if (!$success) {
         $this->lastError = "TextMe failed | Code: $httpCode | Response: " . substr($response ?: '', 0, 200);
      }

      return $success;
   }
}
