<?php

/**
 * Middleware Pipeline
 *
 * Manages the execution of middleware in a pipeline pattern.
 * Handles middleware registration, sorting, and execution.
 *
 * Features:
 * - Middleware registration and management
 * - Priority-based execution order
 * - Pipeline execution with proper chaining
 * - Error handling and recovery
 * - Conditional middleware execution
 * - Performance monitoring
 *
 * @package  AliveChMS\Core\Http
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Middleware.php';
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';

class MiddlewarePipeline
{
   private array $middleware = [];
   private array $globalMiddleware = [];
   private bool $sorted = false;

   /**
    * Add middleware to the pipeline
    *
    * @param Middleware|string $middleware Middleware instance or class name
    * @param bool $global Whether this is global middleware
    * @return self
    */
   public function add($middleware, bool $global = false): self
   {
      if (is_string($middleware)) {
         $middleware = new $middleware();
      }

      if (!$middleware instanceof Middleware) {
         throw new InvalidArgumentException('Middleware must be an instance of Middleware class');
      }

      if ($global) {
         $this->globalMiddleware[] = $middleware;
      } else {
         $this->middleware[] = $middleware;
      }

      $this->sorted = false;
      return $this;
   }

   /**
    * Add multiple middleware at once
    *
    * @param array $middlewares Array of middleware instances or class names
    * @param bool $global Whether these are global middleware
    * @return self
    */
   public function addMany(array $middlewares, bool $global = false): self
   {
      foreach ($middlewares as $middleware) {
         $this->add($middleware, $global);
      }
      return $this;
   }

   /**
    * Add global middleware
    *
    * @param Middleware|string $middleware Middleware instance or class name
    * @return self
    */
   public function addGlobal($middleware): self
   {
      return $this->add($middleware, true);
   }

   /**
    * Remove middleware from pipeline
    *
    * @param string $middlewareClass Class name of middleware to remove
    * @return self
    */
   public function remove(string $middlewareClass): self
   {
      $this->middleware = array_filter($this->middleware, function ($middleware) use ($middlewareClass) {
         return !($middleware instanceof $middlewareClass);
      });

      $this->globalMiddleware = array_filter($this->globalMiddleware, function ($middleware) use ($middlewareClass) {
         return !($middleware instanceof $middlewareClass);
      });

      return $this;
   }

   /**
    * Clear all middleware
    *
    * @param bool $includeGlobal Whether to clear global middleware too
    * @return self
    */
   public function clear(bool $includeGlobal = false): self
   {
      $this->middleware = [];
      if ($includeGlobal) {
         $this->globalMiddleware = [];
      }
      $this->sorted = false;
      return $this;
   }

   /**
    * Get all middleware in execution order
    *
    * @return array Array of middleware instances
    */
   public function getMiddleware(): array
   {
      if (!$this->sorted) {
         $this->sortMiddleware();
      }

      return array_merge($this->globalMiddleware, $this->middleware);
   }

   /**
    * Sort middleware by priority
    */
   private function sortMiddleware(): void
   {
      usort($this->middleware, function (Middleware $a, Middleware $b) {
         return $a->getPriority() <=> $b->getPriority();
      });

      usort($this->globalMiddleware, function (Middleware $a, Middleware $b) {
         return $a->getPriority() <=> $b->getPriority();
      });

      $this->sorted = true;
   }

   /**
    * Execute the middleware pipeline
    *
    * @param Request $request The HTTP request
    * @param callable $destination Final destination (usually route handler)
    * @return Response The HTTP response
    */
   public function execute(Request $request, callable $destination): Response
   {
      $middleware = $this->getMiddleware();

      // Create the pipeline chain
      $pipeline = array_reduce(
         array_reverse($middleware),
         function (callable $next, Middleware $middleware) {
            return function (Request $request) use ($middleware, $next) {
               return $middleware->execute($request, $next);
            };
         },
         $destination
      );

      return $pipeline($request);
   }

   /**
    * Execute middleware pipeline with timing
    *
    * @param Request $request The HTTP request
    * @param callable $destination Final destination
    * @return array Response and execution info
    */
   public function executeWithTiming(Request $request, callable $destination): array
   {
      $startTime = microtime(true);
      $startMemory = memory_get_usage();

      $response = $this->execute($request, $destination);

      $endTime = microtime(true);
      $endMemory = memory_get_usage();

      return [
         'response' => $response,
         'execution_time' => $endTime - $startTime,
         'memory_used' => $endMemory - $startMemory,
         'middleware_count' => count($this->getMiddleware())
      ];
   }

   /**
    * Get middleware execution order for debugging
    *
    * @return array Array of middleware names in execution order
    */
   public function getExecutionOrder(): array
   {
      return array_map(function (Middleware $middleware) {
         return $middleware->getName();
      }, $this->getMiddleware());
   }

   /**
    * Check if specific middleware is registered
    *
    * @param string $middlewareClass Class name to check
    * @return bool Whether middleware is registered
    */
   public function hasMiddleware(string $middlewareClass): bool
   {
      $allMiddleware = array_merge($this->middleware, $this->globalMiddleware);

      foreach ($allMiddleware as $middleware) {
         if ($middleware instanceof $middlewareClass) {
            return true;
         }
      }

      return false;
   }

   /**
    * Get middleware count
    *
    * @return int Total number of middleware
    */
   public function count(): int
   {
      return count($this->middleware) + count($this->globalMiddleware);
   }

   /**
    * Create a new pipeline with conditional middleware
    *
    * @param callable $condition Condition function that receives Request
    * @return ConditionalPipeline
    */
   public function when(callable $condition): ConditionalPipeline
   {
      return new ConditionalPipeline($this, $condition);
   }

   /**
    * Clone the pipeline
    *
    * @return self New pipeline instance
    */
   public function clone(): self
   {
      $clone = new self();
      $clone->middleware = $this->middleware;
      $clone->globalMiddleware = $this->globalMiddleware;
      $clone->sorted = $this->sorted;
      return $clone;
   }
}

/**
 * Conditional Pipeline
 *
 * Allows conditional execution of middleware based on request properties
 */
class ConditionalPipeline
{
   private MiddlewarePipeline $pipeline;
   private $condition;

   public function __construct(MiddlewarePipeline $pipeline, callable $condition)
   {
      $this->pipeline = $pipeline->clone();
      $this->condition = $condition;
   }

   /**
    * Add middleware to conditional pipeline
    *
    * @param Middleware|string $middleware Middleware instance or class name
    * @return self
    */
   public function add($middleware): self
   {
      $this->pipeline->add($middleware);
      return $this;
   }

   /**
    * Execute conditional pipeline
    *
    * @param Request $request The HTTP request
    * @param callable $destination Final destination
    * @return Response The HTTP response
    */
   public function execute(Request $request, callable $destination): Response
   {
      if (($this->condition)($request)) {
         return $this->pipeline->execute($request, $destination);
      }

      return $destination($request);
   }
}
