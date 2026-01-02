<?php

/**
 * Migration Service Provider
 *
 * Registers migration-related services in the DI container.
 *
 * @package  AliveChMS\Core\Providers
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../ServiceProvider.php';
require_once __DIR__ . '/../Database/MigrationManager.php';

class MigrationServiceProvider extends ServiceProvider
{
   public function register(): void
   {
      // Register MigrationManager as singleton
      $this->container->singleton('MigrationManager', function ($container) {
         $database = $container->resolve('Database');
         $migrationsPath = __DIR__ . '/../../migrations';

         return new MigrationManager($database->getConnection(), $migrationsPath);
      });

      // Register SchemaBuilder
      $this->container->bind('SchemaBuilder', function ($container) {
         $database = $container->resolve('Database');
         return new SchemaBuilder($database->getConnection());
      });
   }
}
