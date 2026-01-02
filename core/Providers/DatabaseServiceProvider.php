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

require_once __DIR__ . '/../ServiceProvider.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../ORM.php';
require_once __DIR__ . '/../QueryBuilder.php';

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