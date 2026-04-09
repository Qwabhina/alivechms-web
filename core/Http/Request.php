<?php

declare(strict_types=1);

namespace AliveChMS\Core\Http;

use Exception;

/**
 * HTTP Request Wrapper
 */
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
      $this->rawBody = $rawBody ?? @file_get_contents('php://input');
      $this->headers = $this->parseHeaders();
   }

   public static function createFromGlobals(): self
   {
      return new self();
   }

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

   private function parseHeaders(): array
   {
      $headers = [];
      foreach ($this->server as $key => $value) {
         if (str_starts_with($key, 'HTTP_')) {
            $headerName = str_replace('_', '-', substr($key, 5));
            $headers[strtolower($headerName)] = $value;
         }
      }
      if (isset($this->server['CONTENT_TYPE'])) {
         $headers['content-type'] = $this->server['CONTENT_TYPE'];
      }
      if (isset($this->server['CONTENT_LENGTH'])) {
         $headers['content-length'] = $this->server['CONTENT_LENGTH'];
      }
      return $headers;
   }

   public function getMethod(): string
   {
      return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
   }

   public function getUri(): string
   {
      return $this->server['REQUEST_URI'] ?? '/';
   }

   public function getPath(): string
   {
      $uri = $this->getUri();
      $path = parse_url($uri, PHP_URL_PATH);
      return $path ? trim($path, '/') : '';
   }

   public function query(?string $key = null, $default = null)
   {
      if ($key === null) {
         return $this->query;
      }
      return $this->query[$key] ?? $default;
   }

   public function post(?string $key = null, $default = null)
   {
      if ($key === null) {
         return $this->post;
      }
      return $this->post[$key] ?? $default;
   }

   public function input(?string $key = null, $default = null)
   {
      $data = array_merge($this->post, $this->json() ?? []);
      if ($key === null) {
         return $data;
      }
      return $data[$key] ?? $default;
   }

   public function all(): array
   {
      return array_merge($this->query, $this->post, $this->json() ?? []);
   }

   public function only(array $keys): array
   {
      $data = $this->all();
      return array_intersect_key($data, array_flip($keys));
   }

   public function except(array $keys): array
   {
      $data = $this->all();
      return array_diff_key($data, array_flip($keys));
   }

   public function has(string $key): bool
   {
      return array_key_exists($key, $this->all());
   }

   public function filled(string $key): bool
   {
      $value = $this->input($key);
      return $value !== null && $value !== '';
   }

   public function json(): ?array
   {
      if ($this->json === null && $this->isJson()) {
         $this->json = json_decode($this->rawBody, true);
      }
      return $this->json;
   }

   public function isJson(): bool
   {
      $contentType = $this->header('content-type', '');
      return str_contains($contentType, 'application/json');
   }

   public function getContent(): string
   {
      return $this->rawBody ?? '';
   }

   public function header(string $key, $default = null)
   {
      return $this->headers[strtolower($key)] ?? $default;
   }

   public function headers(): array
   {
      return $this->headers;
   }

   public function bearerToken(): ?string
   {
      $authorization = $this->header('authorization');
      if ($authorization && str_starts_with($authorization, 'Bearer ')) {
         return substr($authorization, 7);
      }
      return null;
   }

   public function server(?string $key = null, $default = null)
   {
      if ($key === null) {
         return $this->server;
      }
      return $this->server[$key] ?? $default;
   }

   public function ip(): string
   {
      $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
      foreach ($ipKeys as $key) {
         if (!empty($this->server[$key])) {
            $ip = $this->server[$key];
            if (str_contains($ip, ',')) {
               $ip = trim(explode(',', $ip)[0]);
            }
            return $ip;
         }
      }
      return '127.0.0.1';
   }

   public function userAgent(): string
   {
      return $this->server['HTTP_USER_AGENT'] ?? '';
   }

   public function isSecure(): bool
   {
      return ($this->server['HTTPS'] ?? 'off') !== 'off' ||
         ($this->server['SERVER_PORT'] ?? 80) == 443;
   }

   public function isAjax(): bool
   {
      return strtolower($this->header('x-requested-with', '')) === 'xmlhttprequest';
   }

   public function file(string $key): ?array
   {
      return $this->files[$key] ?? null;
   }

   public function files(): array
   {
      return $this->files;
   }

   public function hasFile(string $key): bool
   {
      $file = $this->file($key);
      return $file && $file['error'] === UPLOAD_ERR_OK;
   }

   public function cookie(string $key, $default = null)
   {
      return $this->cookies[$key] ?? $default;
   }

   public function setRouteParams(array $params): void
   {
      $this->routeParams = $params;
   }

   public function route(string $key, $default = null)
   {
      return $this->routeParams[$key] ?? $default;
   }

   public function routeParams(): array
   {
      return $this->routeParams;
   }

   public function toArray(): array
   {
      return [
         'method' => $this->getMethod(),
         'uri' => $this->getUri(),
         'path' => $this->getPath(),
         'ip' => $this->ip(),
         'user_agent' => $this->userAgent()
      ];
   }
}
