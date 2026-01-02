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

require_once __DIR__ . '/../ServiceProvider.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Validator.php';
require_once __DIR__ . '/../Cache.php';
require_once __DIR__ . '/../RateLimiter.php';
require_once __DIR__ . '/../Settings.php';
require_once __DIR__ . '/../AuditLog.php';

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
