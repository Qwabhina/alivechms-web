<?php

declare(strict_types=1);

namespace AliveChMS\Core\Http;

use Exception;

/**
 * HTTP Response Wrapper
 */
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

   public static function make($content = '', int $statusCode = 200, array $headers = []): self
   {
      return new self($content, $statusCode, $headers);
   }

   public static function json($data, int $statusCode = 200, array $headers = []): self
   {
      $headers['Content-Type'] = 'application/json; charset=utf-8';
      $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      return new self($content, $statusCode, $headers);
   }

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

   public static function redirect(string $url, int $statusCode = 302): self
   {
      return new self('', $statusCode, ['Location' => $url]);
   }

   public static function notFound(string $message = 'Not Found'): self
   {
      return self::error($message, 404);
   }

   public static function unauthorized(string $message = 'Unauthorized'): self
   {
      return self::error($message, 401);
   }

   public static function forbidden(string $message = 'Forbidden'): self
   {
      return self::error($message, 403);
   }

   public static function validationError(string $message, array $errors): self
   {
      return self::error($message, 422, $errors);
   }

   public function setContent($content): self
   {
      $this->content = $content;
      return $this;
   }

   public function getContent(): mixed
   {
      return $this->content;
   }

   public function setStatusCode(int $statusCode): self
   {
      $this->statusCode = $statusCode;
      return $this;
   }

   public function getStatusCode(): int
   {
      return $this->statusCode;
   }

   public function header(string $name, string $value): self
   {
      $this->headers[$name] = $value;
      return $this;
   }

   public function withHeaders(array $headers): self
   {
      $this->headers = array_merge($this->headers, $headers);
      return $this;
   }

   public function getHeader(string $name): ?string
   {
      return $this->headers[$name] ?? null;
   }

   public function getHeaders(): array
   {
      return $this->headers;
   }

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

   public function send(): void
   {
      if ($this->sent || headers_sent()) {
         echo $this->content;
         $this->sent = true;
         return;
      }
      http_response_code($this->statusCode);
      foreach ($this->headers as $name => $value) {
         header("$name: $value");
      }
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
      echo $this->content;
      $this->sent = true;
   }

   public function sendAndExit(): never
   {
      $this->send();
      exit;
   }

   public static function fromException(Exception $exception): self
   {
      $statusCode = 500;
      $message = 'Internal Server Error';

      if ($exception instanceof ValidationException) {
         return self::validationError('Validation failed', $exception->getErrors());
      }

      if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
         $message = $exception->getMessage();
      }

      return self::error($message, $statusCode);
   }
}
