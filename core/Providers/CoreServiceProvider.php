<?php

/**
 * Core Service Provider
 *
 * Registers core application services in the DI container.
 *
 * @package  AliveChMS\Core\Providers
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

namespace AliveChMS\Core\Providers;

use AliveChMS\Core\System\ServiceProvider;
use AliveChMS\Core\Identity\Auth;
use AliveChMS\Core\System\Validator;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\System\Helpers;

use AliveChMS\Core\Infrastructure\Cache;
use AliveChMS\Core\Infrastructure\RateLimiter;
use AliveChMS\Core\System\Settings;
use AliveChMS\Core\System\AuditLog;

class CoreServiceProvider extends ServiceProvider
{
   public function register(): void
   {
      // Register Auth service
      $this->container->singleton('Auth', function ($container) {
         return new Auth();
      });

      // Register Validator service
      $this->container->bind('Validator', function ($container) {
         return new Validator([], []);
      });

      // Register Cache service
      $this->container->singleton('Cache', function ($container) {
         return new Cache();
      });

      // Register RateLimiter service
      $this->container->singleton('RateLimiter', function ($container) {
         return new RateLimiter();
      });

      // Register Settings service
      $this->container->singleton('Settings', function ($container) {
         return new Settings();
      });

      // Register AuditLog service
      $this->container->bind('AuditLog', function ($container) {
         return new AuditLog();
      });
   }
}
