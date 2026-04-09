<?php

declare(strict_types=1);

namespace AliveChMS\Core\Http;

/**
 * Conditional Pipeline
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

   public function add($middleware): self
   {
      $this->pipeline->add($middleware);
      return $this;
   }

   public function execute(Request $request, callable $destination): Response
   {
      if (($this->condition)($request)) {
         return $this->pipeline->execute($request, $destination);
      }
      return $destination($request);
   }
}
