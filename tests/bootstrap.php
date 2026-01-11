<?php

/**
 * PHPUnit Bootstrap File
 * 
 * Sets up testing environment and loads dependencies
 */

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables for testing
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Set timezone
date_default_timezone_set('Africa/Accra');

// Ensure test cache directory exists
$testCacheDir = __DIR__ . '/../cache/test';
if (!is_dir($testCacheDir)) {
   mkdir($testCacheDir, 0755, true);
}

// Load core classes (since we don't have autoloading yet)
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ORM.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/RateLimiter.php';
require_once __DIR__ . '/../core/Settings.php';
require_once __DIR__ . '/../core/Cache.php';
require_once __DIR__ . '/../core/AuditLog.php';
require_once __DIR__ . '/../core/Container.php';
require_once __DIR__ . '/../core/Application.php';
require_once __DIR__ . '/../core/Http/Request.php';
require_once __DIR__ . '/../core/Http/Response.php';
