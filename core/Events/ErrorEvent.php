<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

use Exception;
use Error;
use RuntimeException;
use LogicException;

/**
 * Error Event
 */
class ErrorEvent extends Event
{
   public function __construct(Exception $exception, array $context = [])
   {
      parent::__construct([
         'exception' => $exception,
         'message' => $exception->getMessage(),
         'code' => $exception->getCode(),
         'file' => $exception->getFile(),
         'line' => $exception->getLine(),
         'trace' => $exception->getTraceAsString(),
         'context' => $context,
         'severity' => $this->determineSeverity($exception)
      ]);
   }

   public function getException(): Exception
   {
      return $this->getData('exception');
   }

   public function getMessage(): string
   {
      return $this->getData('message');
   }

   public function getSeverity(): string
   {
      return $this->getData('severity');
   }

   public function getContext(): array
   {
      return $this->getData('context');
   }

   private function determineSeverity(Exception $exception): string
   {
      if ($exception instanceof Error) {
         return 'critical';
      } elseif ($exception instanceof RuntimeException) {
         return 'error';
      } elseif ($exception instanceof LogicException) {
         return 'warning';
      }
      return 'info';
   }
}
