<?php

/**
 * Entity Service Provider
 *
 * Registers entity/model services in the DI container.
 *
 * @package  AliveChMS\Core\Providers
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../ServiceProvider.php';
require_once __DIR__ . '/../Member.php';
require_once __DIR__ . '/../Expense.php';

class EntityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Member service
        $this->container->bind('Member', function ($container) {
            return new Member();
        });

        // Register Expense service
        $this->container->bind('Expense', function ($container) {
            return new Expense();
        });

        // Add other entity services as needed
        // $this->container->bind('Budget', Budget::class);
        // $this->container->bind('Event', Event::class);
        // etc.
    }
}