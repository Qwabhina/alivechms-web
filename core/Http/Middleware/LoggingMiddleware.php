<?php

/**
 * Logging Middleware
 *
 * Logs HTTP requests and responses for monitoring and debugging.
 * Provides configurable logging levels and data sanitization.
 *
 * @package  AliveChMS\Core\Http\Middleware
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../Middleware.php';

class LoggingMiddleware extends Middleware
{
   private array $config;
   private string $logFile;

   public function __construct(array $config = [])
   {
      $this->config = array_merge([
         'log_requests' => true,
         'log_responses' => true,
         'log_headers' => false,
         'log_body' => false,
         'sanitize_sensitive' => true,
         'sensitive_fields' => ['password', 'token', 'secret', 'key'],
         'max_body_length' => 1000,
         'log_level' => 'info'
      ], $config);

      $this->logFile = __DIR__ . '/../../../logs/http.log';
      $this->ensureLogDirectory();
   }

   public function handle(Request $request, callable $next): Response
   {
      $startTime = microtime(true);
      $requestId = $this->generateRequestId();

      // Log request
      if ($this->config['log_requests']) {
         $this->logRequest($request, $requestId);
      }

      $response = $next($request);

      // Log response
      if ($this->config['log_responses']) {
         $duration = microtime(true) - $startTime;
         $this->logResponse($response, $requestId, $duration);
      }

      return $response;
   }

   public function getPriority(): int
   {
      return 90; // Execute late to capture full request/response cycle
   }

   /**
    * Log HTTP request
    */
   private function logRequest(Request $request, string $requestId): void
   {
      $data = [
         'type' => 'request',
         'request_id' => $requestId,
         'timestamp' => date('c'),
         'method' => $request->getMethod(),
         'uri' => $request->getUri(),
         'ip' => $request->ip(),
         'user_agent' => $request->userAgent()
      ];

      if ($this->config['log_headers']) {
         $data['headers'] = $this->sanitizeData($request->headers());
      }

      if ($this->config['log_body']) {
         $body = $request->getContent();
         if (strlen($body) > $this->config['max_body_length']) {
            $body = substr($body, 0, $this->config['max_body_length']) . '... (truncated)';
         }
         $data['body'] = $this->sanitizeData($body);
      }

      $this->writeLog($data);
   }

   /**
    * Log HTTP response
    */
   private function logResponse(Response $response, string $requestId, float $duration): void
   {
      $data = [
         'type' => 'response',
         'request_id' => $requestId,
         'timestamp' => date('c'),
         'status_code' => $response->getStatusCode(),
         'duration_ms' => round($duration * 1000, 2),
         'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2)
      ];

      if ($this->config['log_headers']) {
         $data['headers'] = $response->getHeaders();
      }

      if ($this->config['log_body']) {
         $content = $response->getContent();
         if (is_string($content) && strlen($content) > $this->config['max_body_length']) {
            $content = substr($content, 0, $this->config['max_body_length']) . '... (truncated)';
         }
         $data['body'] = $this->sanitizeData($content);
      }

      $this->writeLog($data);
   }

   /**
    * Sanitize sensitive data
    */
   private function sanitizeData($data)
   {
      if (!$this->config['sanitize_sensitive']) {
         return $data;
      }

      if (is_array($data)) {
         foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveField($key)) {
               $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
               $data[$key] = $this->sanitizeData($value);
            }
         }
      } elseif (is_string($data)) {
         // Try to decode JSON and sanitize
         $decoded = json_decode($data, true);
         if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($this->sanitizeData($decoded));
         }
      }

      return $data;
   }

   /**
    * Check if field is sensitive
    */
   private function isSensitiveField(string $field): bool
   {
      $field = strtolower($field);

      foreach ($this->config['sensitive_fields'] as $sensitiveField) {
         if (str_contains($field, strtolower($sensitiveField))) {
            return true;
         }
      }

      return false;
   }

   /**
    * Write log entry
    */
   private function writeLog(array $data): void
   {
      $logEntry = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
      file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
   }

   /**
    * Generate unique request ID
    */
   private function generateRequestId(): string
   {
      return uniqid('req_', true);
   }

   /**
    * Ensure log directory exists
    */
   private function ensureLogDirectory(): void
   {
      $logDir = dirname($this->logFile);
      if (!is_dir($logDir)) {
         mkdir($logDir, 0755, true);
      }
   }
}
