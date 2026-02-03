<?php

/**
 * Database Service Provider
 *
 * Registers database-related services in the DI container.
 *
 * @package  AliveChMS\Core\Providers
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

namespace AliveChMS\Core\Providers;

use AliveChMS\Core\System\ServiceProvider;
use AliveChMS\Core\System\Database;
use AliveChMS\Core\System\ORM;
use AliveChMS\Core\QueryBuilder;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Database as singleton
        $this->container->singleton('Database', function ($container) {
            return Database::getInstance();
        });

        // Register ORM with Database dependency
        $this->container->bind('ORM', function ($container) {
            return new ORM();
        });

        // Register QueryBuilder with Database dependency
        $this->container->bind('QueryBuilder', function ($container) {
            $database = $container->resolve('Database');
            return new QueryBuilder($database->getConnection());
        });

        // Register PDO connection
        $this->container->singleton('PDO', function ($container) {
            $database = $container->resolve('Database');
            return $database->getConnection();
        });
    }
}