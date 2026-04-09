<?php
declare(strict_types=1);

namespace AliveChMS\Core\Events;

use Exception;
use Error;
use RuntimeException;
use LogicException;

/**
 * System-related Events
 *
 * Events related to system operations, errors, and lifecycle.
 *
 * @package  AliveChMS\Core\Events
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */



/**
 * Application Started Event
 */
class ApplicationStartedEvent extends Event
{
   public function __construct()
   {
      parent::__construct([
         'php_version' => PHP_VERSION,
         'memory_limit' => ini_get('memory_limit'),
         'max_execution_time' => ini_get('max_execution_time'),
         'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
      ]);
   }
}
