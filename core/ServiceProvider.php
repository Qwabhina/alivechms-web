<?php

/**
 * Service Provider Base Class
 *
 * Base class for organizing service registrations in the DI container.
 * Service providers help organize related services and their dependencies.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

abstract class ServiceProvider
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register services in the container
     */
    abstract public function register(): void;

    /**
     * Boot services after all providers are registered
     */
    public function boot(): void
    {
        // Override in child classes if needed
    }
}