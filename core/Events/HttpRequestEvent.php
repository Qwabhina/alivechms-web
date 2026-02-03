<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

use Exception;
use Error;
use RuntimeException;
use LogicException;

/**
 * HTTP Request Event
 */
class HttpRequestEvent extends Event
{
   public function __construct(string $method, string $uri, array $headers = [], array $data = [])
   {
      parent::__construct([
         'method' => $method,
         'uri' => $uri,
         'headers' => $headers,
         'data' => $data,
         'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
         'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
      ]);
   }

   public function getMethod(): string
   {
      return $this->getData('method');
   }

   public function getUri(): string
   {
      return $this->getData('uri');
   }

   public function getHeaders(): array
   {
      return $this->getData('headers');
   }

   public function isApiRequest(): bool
   {
      return str_starts_with($this->getUri(), '/api/');
   }
}
