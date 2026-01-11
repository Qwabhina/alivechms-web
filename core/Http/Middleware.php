<?php

/**
 * Middleware Base Class
 *
 * Base class for all middleware components. Middleware provides a way to
 * filter HTTP requests entering your application and responses leaving it.
 *
 * Features:
 * - Request/Response filtering
 * - Chain of responsibility pattern
 * - Before and after request processing
 * - Conditional execution
 * - Error handling integration
 *
 * @package  AliveChMS\Core\Http
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

abstract class Middleware
{
   /**
    * Handle the request
    *
    * @param Request $request The HTTP request
    * @param callable $next The next middleware in the pipeline
    * @return Response The HTTP response
    */
   abstract public function handle(Request $request, callable $next): Response;

   /**
    * Determine if the middleware should be executed
    *
    * @param Request $request The HTTP request
    * @return bool Whether to execute this middleware
    */
   public function shouldExecute(Request $request): bool
   {
      return true;
   }

   /**
    * Get middleware priority (lower numbers execute first)
    *
    * @return int Priority value
    */
   public function getPriority(): int
   {
      return 100;
   }

   /**
    * Get middleware name for debugging
    *
    * @return string Middleware name
    */
   public function getName(): string
   {
      return static::class;
   }

   /**
    * Handle middleware errors
    *
    * @param Exception $exception The exception that occurred
    * @param Request $request The HTTP request
    * @return Response Error response
    */
   protected function handleError(Exception $exception, Request $request): Response
   {
      return Response::fromException($exception);
   }

   /**
    * Before request processing hook
    *
    * @param Request $request The HTTP request
    * @return void
    */
   protected function before(Request $request): void
   {
      // Override in subclasses if needed
   }

   /**
    * After response processing hook
    *
    * @param Request $request The HTTP request
    * @param Response $response The HTTP response
    * @return Response Modified response
    */
   protected function after(Request $request, Response $response): Response
   {
      // Override in subclasses if needed
      return $response;
   }

   /**
    * Execute middleware with before/after hooks
    *
    * @param Request $request The HTTP request
    * @param callable $next The next middleware in the pipeline
    * @return Response The HTTP response
    */
   final public function execute(Request $request, callable $next): Response
   {
      try {
         // Check if middleware should execute
         if (!$this->shouldExecute($request)) {
            return $next($request);
         }

         // Before hook
         $this->before($request);

         // Execute middleware
         $response = $this->handle($request, $next);

         // After hook
         return $this->after($request, $response);
      } catch (Exception $e) {
         return $this->handleError($e, $request);
      }
   }
}
