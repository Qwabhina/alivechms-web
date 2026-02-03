<?php
declare(strict_types=1);

namespace AliveChMS\Core\Infrastructure;

use AliveChMS\Core\System\Helpers;

/**
 * Generic HTTP SMS Provider.
 */
class GenericHttpProvider extends BaseSmsProvider
{
   /**
    * @inheritdoc
    */
   public function send(string $to, string $message): bool
   {
      $url = $_ENV['GENERIC_SMS_URL'] ?? '';
      $method = $_ENV['GENERIC_SMS_METHOD'] ?? 'POST';
      $headers = $this->parseHeaders($_ENV['GENERIC_SMS_HEADERS'] ?? '');
      $body = $this->parseBody($_ENV['GENERIC_SMS_BODY'] ?? '', $to, $message);

      if (empty($url)) {
         $this->lastError = 'GENERIC_SMS_URL not configured';
         return false;
      }

      $ch = curl_init($url);
      curl_setopt_array($ch, [
         CURLOPT_CUSTOMREQUEST => $method,
         CURLOPT_POSTFIELDS => is_array($body) ? json_encode($body) : $body,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_TIMEOUT => 20,
         CURLOPT_SSL_VERIFYPEER => true,
         CURLOPT_HTTPHEADER => $headers,
      ]);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $error = curl_error($ch);
      $ch = null;

      $success = $httpCode >= 200 && $httpCode < 300;

      if (!$success) {
         $this->lastError = "Generic SMS failed | Code: $httpCode | Error: $error";
      }

      return $success;
   }

   /**
    * Parse header block into an array.
    *
    * @param string $raw
    * @return array
    */
   private function parseHeaders(string $raw): array
   {
      $headers = [];
      foreach (explode("\n", $raw) as $line) {
         if (trim($line) && strpos($line, ':') !== false) {
            [$key, $val] = explode(':', $line, 2);
            $headers[] = trim($key) . ':' . trim($val);
         }
      }
      return $headers;
   }

   /**
    * Parse message body template.
    *
    * @param string $template
    * @param string $to
    * @param string $message
    * @return string|array
    */
   private function parseBody(string $template, string $to, string $message): array|string
   {
      $replacements = [
         '{to}' => $to,
         '{message}' => $message,
         '{sender}' => $_ENV['SMS_SENDER_ID'] ?? 'AliveChMS'
      ];

      return str_replace(array_keys($replacements), array_values($replacements), $template);
   }
}
