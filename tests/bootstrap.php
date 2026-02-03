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
