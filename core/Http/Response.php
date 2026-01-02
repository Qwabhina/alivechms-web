<?php

/**
 * HTTP Response Wrapper
 *
 * Provides a clean, testable interface for HTTP responses.
 * Handles JSON responses, headers, status codes, and cookies.
 *
 * Features:
 * - Fluent API for building responses
 * - JSON response formatting
 * - Header management
 * - Cookie handling
 * - Status code management
 * - Content type handling
 * - Redirect responses
 *
 * @package  AliveChMS\Core\Http
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

class Response
{
   private mixed $content = '';
   private int $statusCode = 200;
   private array $headers = [];
   private array $cookies = [];
   private bool $sent = false;

   public function __construct($content = '', int $statusCode = 200, array $headers = [])
   {
      $this->content = $content;
      $this->statusCode = $statusCode;
      $this->headers = $headers;
   }

   /**
    * Create a new response
    */
   public static function make($content = '', int $statusCode = 200, array $headers = []): self
   {
      return new self($content, $statusCode, $headers);
   }

   /**
    * Create JSON response
    */
   public static function json($data, int $statusCode = 200, array $headers = []): self
   {
      $headers['Content-Type'] = 'application/json; charset=utf-8';

      $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

      return new self($content, $statusCode, $headers);
   }

   /**
    * Create success JSON response
    */
   public static function success($data = null, string $message = 'Success', int $statusCode = 200): self
   {
      $response = [
         'status' => 'success',
         'message' => $message,
         'timestamp' => date('c')
      ];

      if ($data !== null) {
         $response['data'] = $data;
      }

      return self::json($response, $statusCode);
   }

   /**
    * Create error JSON response
    */
   public static function error(string $message, int $statusCode = 400, array $errors = []): self
   {
      $response = [
         'status' => 'error',
         'message' => $message,
         'code' => $statusCode,
         'timestamp' => date('c')
      ];

      if (!empty($errors)) {
         $response['errors'] = $errors;
      }

      return self::json($response, $statusCode);
   }

   /**
    * Create paginated JSON response
    */
   public static function paginated(array $data, int $total, int $page, int $limit, string $message = 'Success'): self
   {
      return self::success([
         'data' => $data,
         'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int)ceil($total / $limit)
         ]
      ], $message);
   }

   /**
    * Create redirect response
    */
   public static function redirect(string $url, int $statusCode = 302): self
   {
      return new self('', $statusCode, ['Location' => $url]);
   }

   /**
    * Create not found response
    */
   public static function notFound(string $message = 'Not Found'): self
   {
      return self::error($message, 404);
   }

   /**
    * Create unauthorized response
    */
   public static function unauthorized(string $message = 'Unauthorized'): self
   {
      return self::error($message, 401);
   }

   /**
    * Create forbidden response
    */
   public static function forbidden(string $message = 'Forbidden'): self
   {
      return self::error($message, 403);
   }

   /**
    * Create validation error response
    */
   public static function validationError(string $message, array $errors): self
   {
      return self::error($message, 422, $errors);
   }

   /**
    * Create rate limit response
    */
   public static function rateLimited(string $message, int $retryAfter): self
   {
      return self::error($message, 429)->header('Retry-After', (string)$retryAfter);
   }

   /**
    * Set response content
    */
   public function setContent($content): self
   {
      $this->content = $content;
      return $this;
   }

   /**
    * Get response content
    */
   public function getContent(): mixed
   {
      return $this->content;
   }

   /**
    * Set status code
    */
   public function setStatusCode(int $statusCode): self
   {
      $this->statusCode = $statusCode;
      return $this;
   }

   /**
    * Get status code
    */
   public function getStatusCode(): int
   {
      return $this->statusCode;
   }

   /**
    * Set header
    */
   public function header(string $name, string $value): self
   {
      $this->headers[$name] = $value;
      return $this;
   }

   /**
    * Set multiple headers
    */
   public function withHeaders(array $headers): self
   {
      $this->headers = array_merge($this->headers, $headers);
      return $this;
   }

   /**
    * Get header
    */
   public function getHeader(string $name): ?string
   {
      return $this->headers[$name] ?? null;
   }

   /**
    * Get all headers
    */
   public function getHeaders(): array
   {
      return $this->headers;
   }

   /**
    * Set cookie
    */
   public function cookie(
      string $name,
      string $value,
      int $expires = 0,
      string $path = '/',
      string $domain = '',
      bool $secure = false,
      bool $httpOnly = true
   ): self {
      $this->cookies[] = [
         'name' => $name,
         'value' => $value,
         'expires' => $expires,
         'path' => $path,
         'domain' => $domain,
         'secure' => $secure,
         'httpOnly' => $httpOnly
      ];
      return $this;
   }

   /**
    * Get cookies
    */
   public function getCookies(): array
   {
      return $this->cookies;
   }

   /**
    * Set content type
    */
   public function contentType(string $contentType): self
   {
      return $this->header('Content-Type', $contentType);
   }

   /**
    * Set cache control
    */
   public function cacheControl(string $cacheControl): self
   {
      return $this->header('Cache-Control', $cacheControl);
   }

   /**
    * Disable caching
    */
   public function noCache(): self
   {
      return $this->withHeaders([
         'Cache-Control' => 'no-cache, no-store, must-revalidate',
         'Pragma' => 'no-cache',
         'Expires' => '0'
      ]);
   }

   /**
    * Set CORS headers
    */
   public function cors(
      string $origin = '*',
      string $methods = 'GET, POST, PUT, DELETE, OPTIONS',
      string $headers = 'Content-Type, Authorization, X-Requested-With'
   ): self {
      return $this->withHeaders([
         'Access-Control-Allow-Origin' => $origin,
         'Access-Control-Allow-Methods' => $methods,
         'Access-Control-Allow-Headers' => $headers,
         'Access-Control-Allow-Credentials' => 'true'
      ]);
   }

   /**
    * Send the response
    */
   public function send(): void
   {
      if ($this->sent) {
         return;
      }

      // Send status code
      http_response_code($this->statusCode);

      // Send headers
      foreach ($this->headers as $name => $value) {
         header("$name: $value");
      }

      // Send cookies
      foreach ($this->cookies as $cookie) {
         setcookie(
            $cookie['name'],
            $cookie['value'],
            $cookie['expires'],
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httpOnly']
         );
      }

      // Send content
      echo $this->content;

      $this->sent = true;
   }

   /**
    * Send and exit
    */
   public function sendAndExit(): never
   {
      $this->send();
      exit;
   }

   /**
    * Check if response was sent
    */
   public function isSent(): bool
   {
      return $this->sent;
   }

   /**
    * Get response as string
    */
   public function __toString(): string
   {
      return (string)$this->content;
   }

   /**
    * Get response as array (for testing)
    */
   public function toArray(): array
   {
      $content = $this->content;

      // Try to decode JSON content for easier testing
      if (is_string($content) && str_starts_with($this->getHeader('Content-Type') ?? '', 'application/json')) {
         $decoded = json_decode($content, true);
         if (json_last_error() === JSON_ERROR_NONE) {
            $content = $decoded;
         }
      }

      return [
         'status_code' => $this->statusCode,
         'headers' => $this->headers,
         'cookies' => $this->cookies,
         'content' => $content,
         'sent' => $this->sent
      ];
   }

   /**
    * Create response from exception
    */
   public static function fromException(Exception $exception): self
   {
      $statusCode = 500;
      $message = 'Internal Server Error';

      // Handle specific exception types
      if ($exception instanceof ValidationException) {
         return self::validationError('Validation failed', $exception->getErrors());
      }

      // In development, show actual error
      if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
         $message = $exception->getMessage();
      }

      return self::error($message, $statusCode);
   }
}
