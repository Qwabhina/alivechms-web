<?php

/**
 * Cache System Test Runner
 *
 * Runs comprehensive tests for the cache system including drivers,
 * manager, and service provider functionality.
 *
 * @package  AliveChMS
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Include required files
require_once __DIR__ . '/tests/Unit/CacheSystemTest.php';

try {
    echo "AliveChMS Cache System Test Suite\n";
    echo "=================================\n\n";

    // Create and run tests
    $tester = new CacheSystemTest();
    $results = $tester->runAllTests();

    // Exit with appropriate code
    $failed = count(array_filter($results, fn($r) => !$r['passed']));
    exit($failed > 0 ? 1 : 0);

} catch (Exception $e) {
    echo "Test execution failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "Fatal error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}