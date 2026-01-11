<?php

/**
 * System-related Events
 *
 * Events related to system operations, errors, and lifecycle.
 *
 * @package  AliveChMS\Core\Events
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Event.php';

/**
 * Application Started Event
 */
class ApplicationStartedEvent extends Event
{
   public function __construct()
   {
      parent::__construct([
         'php_version' => PHP_VERSION,
         'memory_limit' => ini_get('memory_limit'),
         'max_execution_time' => ini_get('max_execution_time'),
         'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
      ]);
   }
}

/**
 * Database Query Event
 */
class DatabaseQueryEvent extends Event
{
   public function __construct(string $query, array $bindings = [], float $duration = 0.0)
   {
      parent::__construct([
         'query' => $query,
         'bindings' => $bindings,
         'duration' => $duration,
         'connection' => 'default'
      ]);
   }

   public function getQuery(): string
   {
      return $this->getData('query');
   }

   public function getBindings(): array
   {
      return $this->getData('bindings');
   }

   public function getDuration(): float
   {
      return $this->getData('duration');
   }

   public function isSlow(float $threshold = 1.0): bool
   {
      return $this->getDuration() > $threshold;
   }
}

/**
 * Cache Hit Event
 */
class CacheHitEvent extends Event
{
   public function __construct(string $key, $value, string $store = 'default')
   {
      parent::__construct([
         'key' => $key,
         'value' => $value,
         'store' => $store,
         'hit' => true
      ]);
   }

   public function getKey(): string
   {
      return $this->getData('key');
   }

   public function getValue()
   {
      return $this->getData('value');
   }
}

/**
 * Cache Miss Event
 */
class CacheMissEvent extends Event
{
   public function __construct(string $key, string $store = 'default')
   {
      parent::__construct([
         'key' => $key,
         'store' => $store,
         'hit' => false
      ]);
   }

   public function getKey(): string
   {
      return $this->getData('key');
   }
}

/**
 * Error Event
 */
class ErrorEvent extends Event
{
   public function __construct(Exception $exception, array $context = [])
   {
      parent::__construct([
         'exception' => $exception,
         'message' => $exception->getMessage(),
         'code' => $exception->getCode(),
         'file' => $exception->getFile(),
         'line' => $exception->getLine(),
         'trace' => $exception->getTraceAsString(),
         'context' => $context,
         'severity' => $this->determineSeverity($exception)
      ]);
   }

   public function getException(): Exception
   {
      return $this->getData('exception');
   }

   public function getMessage(): string
   {
      return $this->getData('message');
   }

   public function getSeverity(): string
   {
      return $this->getData('severity');
   }

   public function getContext(): array
   {
      return $this->getData('context');
   }

   private function determineSeverity(Exception $exception): string
   {
      if ($exception instanceof Error) {
         return 'critical';
      } elseif ($exception instanceof RuntimeException) {
         return 'error';
      } elseif ($exception instanceof LogicException) {
         return 'warning';
      }
      return 'info';
   }
}

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

/**
 * HTTP Response Event
 */
class HttpResponseEvent extends Event
{
   public function __construct(int $statusCode, array $headers = [], float $duration = 0.0)
   {
      parent::__construct([
         'status_code' => $statusCode,
         'headers' => $headers,
         'duration' => $duration,
         'memory_usage' => memory_get_usage(true),
         'peak_memory' => memory_get_peak_usage(true)
      ]);
   }

   public function getStatusCode(): int
   {
      return $this->getData('status_code');
   }

   public function getDuration(): float
   {
      return $this->getData('duration');
   }

   public function isSuccessful(): bool
   {
      return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
   }

   public function isError(): bool
   {
      return $this->getStatusCode() >= 400;
   }
}
