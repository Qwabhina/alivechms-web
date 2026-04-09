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

namespace AliveChMS\Core\Providers;

use AliveChMS\Core\System\ServiceProvider;
use AliveChMS\Core\People\Member;
use AliveChMS\Core\Financial\Expense;
use AliveChMS\Core\Operations\Group;
use AliveChMS\Core\Operations\GroupType;
use AliveChMS\Core\Financial\Budget;
use AliveChMS\Core\Operations\Event;
use AliveChMS\Core\Services\MoneyValidator;
use AliveChMS\Core\Operations\Dashboard;

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

        $this->container->bind('Group', function ($container) {
            return new Group();
        });

        $this->container->bind('GroupType', function ($container) {
            return new GroupType();
        });

        $this->container->bind('Budget', function ($container) {
            return new Budget();
        });

        $this->container->bind('Dashboard', function ($container) {
            return new Dashboard();
        });

        $this->container->bind('Event', function ($container) {
            return new Event();
        });
    }
}