<?php

/**
 * HTTP Request Wrapper
 *
 * Provides a clean, testable interface for HTTP request data.
 * Encapsulates $_GET, $_POST, $_SERVER, headers, and request body.
 *
 * Features:
 * - Clean API for accessing request data
 * - Input validation and sanitization
 * - File upload handling
 * - JSON payload parsing
 * - Header management
 * - Route parameter extraction
 *
 * @package  AliveChMS\Core\Http
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

class Request
{
   private array $query;
   private array $post;
   private array $server;
   private array $headers;
   private array $files;
   private array $cookies;
   private ?array $json = null;
   private ?string $rawBody = null;
   private array $routeParams = [];

   public function __construct(
      ?array $query = null,
      ?array $post = null,
      ?array $server = null,
      ?array $files = null,
      ?array $cookies = null,
      ?string $rawBody = null
   ) {
      $this->query = $query ?? $_GET;
      $this->post = $post ?? $_POST;
      $this->server = $server ?? $_SERVER;
      $this->files = $files ?? $_FILES;
      $this->cookies = $cookies ?? $_COOKIE;
      $this->rawBody = $rawBody ?? file_get_contents('php://input');
      $this->headers = $this->parseHeaders();
   }

   /**
    * Create request from globals
    */
   public static function createFromGlobals(): self
   {
      return new self();
   }

   /**
    * Create request for testing
    */
   public static function create(
      string $uri = '/',
      string $method = 'GET',
      array $parameters = [],
      array $server = [],
      ?string $content = null
   ): self {
      $server = array_merge([
         'REQUEST_URI' => $uri,
         'REQUEST_METHOD' => $method,
         'HTTP_HOST' => 'localhost',
         'SERVER_NAME' => 'localhost',
         'SERVER_PORT' => 80,
         'HTTPS' => 'off'
      ], $server);

      $query = [];
      $post = [];

      if ($method === 'GET') {
         $query = $parameters;
      } else {
         $post = $parameters;
      }

      return new self($query, $post, $server, [], [], $content);
   }

   /**
    * Parse headers from server variables
    */
   private function parseHeaders(): array
   {
      $headers = [];

      foreach ($this->server as $key => $value) {
         if (str_starts_with($key, 'HTTP_')) {
            $headerName = str_replace('_', '-', substr($key, 5));
            $headers[strtolower($headerName)] = $value;
         }
      }

      // Add special headers
      if (isset($this->server['CONTENT_TYPE'])) {
         $headers['content-type'] = $this->server['CONTENT_TYPE'];
      }
      if (isset($this->server['CONTENT_LENGTH'])) {
         $headers['content-length'] = $this->server['CONTENT_LENGTH'];
      }

      return $headers;
   }

   /**
    * Get request method
    */
   public function getMethod(): string
   {
      return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
   }

   /**
    * Get request URI
    */
   public function getUri(): string
   {
      return $this->server['REQUEST_URI'] ?? '/';
   }

   /**
    * Get request path (without query string)
    */
   public function getPath(): string
   {
      $uri = $this->getUri();
      $path = parse_url($uri, PHP_URL_PATH);
      return $path ? trim($path, '/') : '';
   }

   /**
    * Get query string parameters
    */
   public function query(?string $key = null, $default = null)
   {
      if ($key === null) {
         return $this->query;
      }
      return $this->query[$key] ?? $default;
   }

   /**
    * Get POST data
    */
   public function post(?string $key = null, $default = null)
   {
      if ($key === null) {
         return $this->post;
      }
      return $this->post[$key] ?? $default;
   }

   /**
    * Get input data (POST or JSON)
    */
   public function input(?string $key = null, $default = null)
   {
      $data = array_merge($this->post, $this->json() ?? []);

      if ($key === null) {
         return $data;
      }
      return $data[$key] ?? $default;
   }

   /**
    * Get all input data
    */
   public function all(): array
   {
      return array_merge($this->query, $this->post, $this->json() ?? []);
   }

   /**
    * Get only specified keys from input
    */
   public function only(array $keys): array
   {
      $data = $this->all();
      return array_intersect_key($data, array_flip($keys));
   }

   /**
    * Get all except specified keys from input
    */
   public function except(array $keys): array
   {
      $data = $this->all();
      return array_diff_key($data, array_flip($keys));
   }

   /**
    * Check if input has key
    */
   public function has(string $key): bool
   {
      return array_key_exists($key, $this->all());
   }

   /**
    * Check if input has non-empty value for key
    */
   public function filled(string $key): bool
   {
      $value = $this->input($key);
      return $value !== null && $value !== '';
   }

   /**
    * Get JSON payload
    */
   public function json(): ?array
   {
      if ($this->json === null && $this->isJson()) {
         $this->json = json_decode($this->rawBody, true);
      }
      return $this->json;
   }

   /**
    * Check if request contains JSON
    */
   public function isJson(): bool
   {
      $contentType = $this->header('content-type', '');
      return str_contains($contentType, 'application/json');
   }

   /**
    * Get raw request body
    */
   public function getContent(): string
   {
      return $this->rawBody ?? '';
   }

   /**
    * Get header value
    */
   public function header(string $key, $default = null)
   {
      return $this->headers[strtolower($key)] ?? $default;
   }

   /**
    * Get all headers
    */
   public function headers(): array
   {
      return $this->headers;
   }

   /**
    * Get bearer token from Authorization header
    */
   public function bearerToken(): ?string
   {
      $authorization = $this->header('authorization');

      if ($authorization && str_starts_with($authorization, 'Bearer ')) {
         return substr($authorization, 7);
      }

      return null;
   }

   /**
    * Get server variable
    */
   public function server(?string $key = null, $default = null)
   {
      if ($key === null) {
         return $this->server;
      }
      return $this->server[$key] ?? $default;
   }

   /**
    * Get client IP address
    */
   public function ip(): string
   {
      // Check for IP from various headers (proxy-aware)
      $ipKeys = [
         'HTTP_CLIENT_IP',
         'HTTP_X_FORWARDED_FOR',
         'HTTP_X_FORWARDED',
         'HTTP_X_CLUSTER_CLIENT_IP',
         'HTTP_FORWARDED_FOR',
         'HTTP_FORWARDED',
         'REMOTE_ADDR'
      ];

      foreach ($ipKeys as $key) {
         if (!empty($this->server[$key])) {
            $ip = $this->server[$key];
            // Handle comma-separated IPs (X-Forwarded-For)
            if (str_contains($ip, ',')) {
               $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
               return $ip;
            }
         }
      }

      return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
   }

   /**
    * Get user agent
    */
   public function userAgent(): string
   {
      return $this->server['HTTP_USER_AGENT'] ?? '';
   }

   /**
    * Check if request is HTTPS
    */
   public function isSecure(): bool
   {
      return ($this->server['HTTPS'] ?? 'off') !== 'off' ||
         ($this->server['SERVER_PORT'] ?? 80) == 443 ||
         strtolower($this->header('x-forwarded-proto', '')) === 'https';
   }

   /**
    * Check if request is AJAX
    */
   public function isAjax(): bool
   {
      return strtolower($this->header('x-requested-with', '')) === 'xmlhttprequest';
   }

   /**
    * Get uploaded files
    */
   public function file(string $key): ?array
   {
      return $this->files[$key] ?? null;
   }

   /**
    * Get all uploaded files
    */
   public function files(): array
   {
      return $this->files;
   }

   /**
    * Check if file was uploaded
    */
   public function hasFile(string $key): bool
   {
      $file = $this->file($key);
      return $file && $file['error'] === UPLOAD_ERR_OK;
   }

   /**
    * Get cookie value
    */
   public function cookie(string $key, $default = null)
   {
      return $this->cookies[$key] ?? $default;
   }

   /**
    * Set route parameters (for URL routing)
    */
   public function setRouteParams(array $params): void
   {
      $this->routeParams = $params;
   }

   /**
    * Get route parameter
    */
   public function route(string $key, $default = null)
   {
      return $this->routeParams[$key] ?? $default;
   }

   /**
    * Get all route parameters
    */
   public function routeParams(): array
   {
      return $this->routeParams;
   }

   /**
    * Validate input data
    */
   public function validate(array $rules): array
   {
      $validator = new Validator($this->all(), $rules);

      if (!$validator->validate()) {
         throw new ValidationException('Validation failed', $validator->errors());
      }

      return $validator->validated();
   }

   /**
    * Get request as array (for debugging)
    */
   public function toArray(): array
   {
      return [
         'method' => $this->getMethod(),
         'uri' => $this->getUri(),
         'path' => $this->getPath(),
         'query' => $this->query,
         'post' => $this->post,
         'json' => $this->json(),
         'headers' => $this->headers,
         'files' => array_keys($this->files),
         'ip' => $this->ip(),
         'user_agent' => $this->userAgent(),
         'is_secure' => $this->isSecure(),
         'is_ajax' => $this->isAjax()
      ];
   }
}

/**
 * Validation Exception
 */
class ValidationException extends Exception
{
   private array $errors;

   public function __construct(string $message, array $errors = [])
   {
      parent::__construct($message);
      $this->errors = $errors;
   }

   public function getErrors(): array
   {
      return $this->errors;
   }
}
