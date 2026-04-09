<?php

declare(strict_types=1);

namespace AliveChMS\Core\Http;

use InvalidArgumentException;

/**
 * Middleware Pipeline
 */
class MiddlewarePipeline
{
   private array $middleware = [];
   private array $globalMiddleware = [];
   private bool $sorted = false;

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

   public function addGlobal($middleware): self
   {
      return $this->add($middleware, true);
   }

   public function getMiddleware(): array
   {
      if (!$this->sorted) {
         $this->sortMiddleware();
      }
      return array_merge($this->globalMiddleware, $this->middleware);
   }

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

   public function execute(Request $request, callable $destination): Response
   {
      $middleware = $this->getMiddleware();
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

   public function when(callable $condition): ConditionalPipeline
   {
      return new ConditionalPipeline($this, $condition);
   }

   public function clone(): self
   {
      $clone = new self();
      $clone->middleware = $this->middleware;
      $clone->globalMiddleware = $this->globalMiddleware;
      $clone->sorted = $this->sorted;
      return $clone;
   }
}
