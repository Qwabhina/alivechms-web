<?php

/**
 * CORS Middleware
 *
 * Handles Cross-Origin Resource Sharing (CORS) headers for API requests.
 * Allows configuration of allowed origins, methods, and headers.
 *
 * @package  AliveChMS\Core\Http\Middleware
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../Middleware.php';

class CorsMiddleware extends Middleware
{
   private array $config;

   public function __construct(array $config = [])
   {
      $this->config = array_merge([
         'allowed_origins' => ['*'],
         'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
         'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
         'exposed_headers' => [],
         'max_age' => 86400, // 24 hours
         'allow_credentials' => true
      ], $config);
   }

   public function handle(Request $request, callable $next): Response
   {
      // Handle preflight OPTIONS request
      if ($request->getMethod() === 'OPTIONS') {
         return $this->handlePreflightRequest($request);
      }

      // Process the request
      $response = $next($request);

      // Add CORS headers to response
      return $this->addCorsHeaders($request, $response);
   }

   public function getPriority(): int
   {
      return 10; // Execute early
   }

   /**
    * Handle preflight OPTIONS request
    */
   private function handlePreflightRequest(Request $request): Response
   {
      $response = Response::make('', 200);

      $origin = $request->header('origin');
      if ($this->isOriginAllowed($origin)) {
         $response->header('Access-Control-Allow-Origin', $origin);
      }

      $response->header('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']));
      $response->header('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']));
      $response->header('Access-Control-Max-Age', (string)$this->config['max_age']);

      if ($this->config['allow_credentials']) {
         $response->header('Access-Control-Allow-Credentials', 'true');
      }

      return $response;
   }

   /**
    * Add CORS headers to response
    */
   private function addCorsHeaders(Request $request, Response $response): Response
   {
      $origin = $request->header('origin');

      if ($this->isOriginAllowed($origin)) {
         $response->header('Access-Control-Allow-Origin', $origin);
      }

      if (!empty($this->config['exposed_headers'])) {
         $response->header('Access-Control-Expose-Headers', implode(', ', $this->config['exposed_headers']));
      }

      if ($this->config['allow_credentials']) {
         $response->header('Access-Control-Allow-Credentials', 'true');
      }

      return $response;
   }

   /**
    * Check if origin is allowed
    */
   private function isOriginAllowed(?string $origin): bool
   {
      if (!$origin) {
         return false;
      }

      if (in_array('*', $this->config['allowed_origins'])) {
         return true;
      }

      return in_array($origin, $this->config['allowed_origins']);
   }
}
