<?php

/**
 * Dependency Injection Container
 *
 * Simple, lightweight DI container for managing dependencies and reducing
 * tight coupling between classes. Supports singleton and factory patterns.
 *
 * Features:
 * - Service registration and resolution
 * - Singleton pattern support
 * - Factory pattern support
 * - Automatic constructor injection
 * - Interface binding
 * - Circular dependency detection
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

class Container
{
   private static ?self $instance = null;
   private array $bindings = [];
   private array $instances = [];
   private array $resolving = [];

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
    * Bind a service to the container
    *
    * @param string $abstract Service identifier (class name or interface)
    * @param mixed $concrete Implementation (class name, closure, or instance)
    * @param bool $singleton Whether to treat as singleton
    */
   public function bind(string $abstract, $concrete = null, bool $singleton = false): void
   {
      if ($concrete === null) {
         $concrete = $abstract;
      }

      $this->bindings[$abstract] = [
         'concrete' => $concrete,
         'singleton' => $singleton
      ];

      // Remove existing instance if rebinding
      unset($this->instances[$abstract]);
   }

   /**
    * Bind a service as singleton
    *
    * @param string $abstract Service identifier
    * @param mixed $concrete Implementation
    */
   public function singleton(string $abstract, $concrete = null): void
   {
      $this->bind($abstract, $concrete, true);
   }

   /**
    * Register an existing instance as singleton
    *
    * @param string $abstract Service identifier
    * @param mixed $instance Existing instance
    */
   public function instance(string $abstract, $instance): void
   {
      $this->instances[$abstract] = $instance;
   }

   /**
    * Resolve a service from the container
    *
    * @param string $abstract Service identifier
    * @return mixed Resolved service instance
    * @throws Exception If service cannot be resolved
    */
   public function resolve(string $abstract)
   {
      // Check for circular dependencies
      if (isset($this->resolving[$abstract])) {
         throw new Exception("Circular dependency detected for: $abstract");
      }

      // Return existing singleton instance
      if (isset($this->instances[$abstract])) {
         return $this->instances[$abstract];
      }

      $this->resolving[$abstract] = true;

      try {
         $concrete = $this->getConcrete($abstract);
         $instance = $this->build($concrete);

         // Store singleton instances
         if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['singleton']) {
            $this->instances[$abstract] = $instance;
         }

         unset($this->resolving[$abstract]);
         return $instance;
      } catch (Exception $e) {
         unset($this->resolving[$abstract]);
         throw $e;
      }
   }

   /**
    * Get concrete implementation for abstract
    */
   private function getConcrete(string $abstract)
   {
      if (isset($this->bindings[$abstract])) {
         return $this->bindings[$abstract]['concrete'];
      }

      return $abstract;
   }

   /**
    * Build an instance of the given concrete
    */
   private function build($concrete)
   {
      // If concrete is a closure, call it
      if ($concrete instanceof Closure) {
         return $concrete($this);
      }

      // If concrete is not a class name, return as-is
      if (!is_string($concrete) || !class_exists($concrete)) {
         return $concrete;
      }

      $reflector = new ReflectionClass($concrete);

      // Check if class is instantiable
      if (!$reflector->isInstantiable()) {
         throw new Exception("Class $concrete is not instantiable");
      }

      $constructor = $reflector->getConstructor();

      // If no constructor, create instance directly
      if ($constructor === null) {
         return new $concrete();
      }

      // Resolve constructor dependencies
      $dependencies = $this->resolveDependencies($constructor->getParameters());

      return $reflector->newInstanceArgs($dependencies);
   }

   /**
    * Resolve constructor dependencies
    */
   private function resolveDependencies(array $parameters): array
   {
      $dependencies = [];

      foreach ($parameters as $parameter) {
         $type = $parameter->getType();

         if ($type === null) {
            // No type hint, check for default value
            if ($parameter->isDefaultValueAvailable()) {
               $dependencies[] = $parameter->getDefaultValue();
            } else {
               throw new Exception("Cannot resolve parameter: {$parameter->getName()}");
            }
         } elseif ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            // Class dependency
            $className = $type->getName();
            $dependencies[] = $this->resolve($className);
         } else {
            // Built-in type, check for default value
            if ($parameter->isDefaultValueAvailable()) {
               $dependencies[] = $parameter->getDefaultValue();
            } else {
               throw new Exception("Cannot resolve built-in parameter: {$parameter->getName()}");
            }
         }
      }

      return $dependencies;
   }

   /**
    * Create an alias for a service
    *
    * @param string $abstract Original service identifier
    * @param string $alias Alias name
    */
   public function alias(string $abstract, string $alias): void
   {
      $this->bindings[$alias] = [
         'concrete' => $abstract,
         'singleton' => false
      ];
   }

   /**
    * Check if service is bound
    */
   public function bound(string $abstract): bool
   {
      return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
   }

   /**
    * Remove a binding
    */
   public function forget(string $abstract): void
   {
      unset($this->bindings[$abstract], $this->instances[$abstract]);
   }

   /**
    * Get all bindings
    */
   public function getBindings(): array
   {
      return $this->bindings;
   }

   /**
    * Clear all bindings and instances
    */
   public function flush(): void
   {
      $this->bindings = [];
      $this->instances = [];
      $this->resolving = [];
   }

   /**
    * Magic method to resolve services
    */
   public function __get(string $name)
   {
      return $this->resolve($name);
   }

   /**
    * Static helper methods for global access
    */
   public static function bindStatic(string $abstract, $concrete = null, bool $singleton = false): void
   {
      self::getInstance()->bind($abstract, $concrete, $singleton);
   }

   public static function singletonStatic(string $abstract, $concrete = null): void
   {
      self::getInstance()->singleton($abstract, $concrete);
   }

   public static function resolveStatic(string $abstract)
   {
      return self::getInstance()->resolve($abstract);
   }

   public static function instanceStatic(string $abstract, $instance): void
   {
      self::getInstance()->instance($abstract, $instance);
   }
}
