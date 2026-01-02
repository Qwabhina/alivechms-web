<?php

/**
 * Application Bootstrap Class
 *
 * Bootstraps the application with dependency injection container
 * and service providers. Manages the application lifecycle.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Container.php';
require_once __DIR__ . '/ServiceProvider.php';
require_once __DIR__ . '/Providers/DatabaseServiceProvider.php';
require_once __DIR__ . '/Providers/CoreServiceProvider.php';
require_once __DIR__ . '/Providers/EntityServiceProvider.php';
require_once __DIR__ . '/Providers/MigrationServiceProvider.php';
require_once __DIR__ . '/Providers/EventServiceProvider.php';
require_once __DIR__ . '/Providers/CacheServiceProvider.php';

class Application
{
   private static ?self $instance = null;
   private Container $container;
   private array $serviceProviders = [];
   private bool $booted = false;

   private function __construct()
   {
      $this->container = Container::getInstance();
      $this->registerBaseServices();
   }

   /**
    * Get singleton instance
    */
   public static function getInstance(): self
   {
      if (self::$instance === null) {
         self::$instance = new self();
      }
      return self::$instance;
   }

   /**
    * Get the container instance
    */
   public function getContainer(): Container
   {
      return $this->container;
   }

   /**
    * Register base services
    */
   private function registerBaseServices(): void
   {
      // Register the container itself
      $this->container->instance('Container', $this->container);
      $this->container->instance('Application', $this);
   }

   /**
    * Register a service provider
    */
   public function register(string $providerClass): void
   {
      if (isset($this->serviceProviders[$providerClass])) {
         return; // Already registered
      }

      $provider = new $providerClass($this->container);
      $provider->register();

      $this->serviceProviders[$providerClass] = $provider;
   }

   /**
    * Boot all registered service providers
    */
   public function boot(): void
   {
      if ($this->booted) {
         return;
      }

      foreach ($this->serviceProviders as $provider) {
         $provider->boot();
      }

      $this->booted = true;
   }

   /**
    * Bootstrap the application with default service providers
    */
   public function bootstrap(): void
   {
      // Register core service providers
      $this->register(DatabaseServiceProvider::class);
      $this->register(CoreServiceProvider::class);
      $this->register(EntityServiceProvider::class);
      $this->register(MigrationServiceProvider::class);
      $this->register(EventServiceProvider::class);
      $this->register(CacheServiceProvider::class);

      // Boot all providers
      $this->boot();
   }

   /**
    * Resolve a service from the container
    */
   public function make(string $abstract)
   {
      return $this->container->resolve($abstract);
   }

   /**
    * Bind a service to the container
    */
   public function bindService(string $abstract, $concrete = null, bool $singleton = false): void
   {
      $this->container->bind($abstract, $concrete, $singleton);
   }

   /**
    * Bind a singleton service
    */
   public function singletonService(string $abstract, $concrete = null): void
   {
      $this->container->singleton($abstract, $concrete);
   }

   /**
    * Check if service is bound
    */
   public function bound(string $abstract): bool
   {
      return $this->container->bound($abstract);
   }

   /**
    * Static helper methods
    */
   public static function resolve(string $abstract)
   {
      return self::getInstance()->make($abstract);
   }

   public static function bind(string $abstract, $concrete = null, bool $singleton = false): void
   {
      self::getInstance()->bindService($abstract, $concrete, $singleton);
   }

   public static function singleton(string $abstract, $concrete = null): void
   {
      self::getInstance()->singletonService($abstract, $concrete);
   }
}
