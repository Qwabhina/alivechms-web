<?php

/**
 * AliveChMS Backend API – Main Entry Point
 *
 * Single entry point for the entire REST API.
 * Initialises environment, security headers, loads dependencies,
 * and dispatches requests via clean URL routing.
 *
 * @package  AliveChMS
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

// Block CLI access
if (php_sapi_name() === 'cli') {
    die('Web-only access permitted.');
}

// Environment-aware error handling
$appEnv = $_ENV['APP_ENV'] ?? 'development';

if ($appEnv === 'production') {
    // Production: Log errors, don't display them
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED, ~E_NOTICE);
} else {
    // Development: Display errors for debugging
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

date_default_timezone_set('Africa/Accra');

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set environment variable for Apache .htaccess (for HTTPS enforcement)
$appEnv = $_ENV['APP_ENV'] ?? 'development';
apache_setenv('APP_ENV', $appEnv);

// Core dependencies
require_once __DIR__ . '/core/Application.php';
require_once __DIR__ . '/core/ResponseHelper.php';

// Bootstrap the application with DI container
$app = Application::getInstance();
$app->bootstrap();

// Legacy includes for backward compatibility (will be removed in Phase 3)
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/ORM.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/core/BaseRoute.php';
require_once __DIR__ . '/core/Validator.php';
require_once __DIR__ . '/core/RateLimiter.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/SettingsHelper.php';

// Security headers
header('Content-Type: application/json; charset=utf-8');
Helpers::addCorsHeaders();

// Basic input sanitization
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        if (is_string($value)) {
            $_GET[$key] = Helpers::sanitize($value, 'string');
        }
    }
}

if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        if (is_string($value)) {
            $_POST[$key] = Helpers::sanitize($value, 'string');
        }
    }
}

// Extract clean path
$rawPath = $_GET['path'] ?? '';
$path    = trim($rawPath, '/');
$path    = preg_replace('#/{2,}#', '/', $path); // Remove double slashes

// Block path traversal
if (str_contains($path, '..') || str_contains($path, "\0")) {
    Helpers::logError("Path traversal blocked: $rawPath");
    ResponseHelper::error('Invalid request path', 400);
}

if ($path === '') {
    header('Location: /public');
    exit;
}

$pathParts = $path !== '' ? explode('/', $path) : [];
$section   = $pathParts[0] ?? '';
$method    = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$token     = Auth::getBearerToken();

// Make route variables available to route files
global $method, $path, $pathParts;

// Master route map
$routes = [
    'auth'            => 'AuthRoutes.php',
    'audit'           => 'AuditRoutes.php',
    'branch'          => 'BranchRoutes.php',
    'member'          => 'MemberRoutes.php',
    'family'          => 'FamilyRoutes.php',
    'contribution'    => 'ContributionRoutes.php',
    'pledge'          => 'PledgeRoutes.php',
    'expense'         => 'ExpenseRoutes.php',
    'expensecategory' => 'ExpenseCategoryRoutes.php',
    'budget'          => 'BudgetRoutes.php',
    'event'           => 'EventRoutes.php',
    'dashboard'       => 'DashboardRoutes.php',
    'finance'         => 'FinanceRoutes.php',
    'fiscalyear'      => 'FiscalYearRoutes.php',
    'group'           => 'GroupRoutes.php',
    'grouptype'       => 'GroupRoutes.php',
    'membershiptype'  => 'MembershipTypeRoutes.php',
    'role'            => 'RoleRoutes.php',
    'settings'        => 'SettingsRoutes.php',
    'permission'      => 'PermissionRoutes.php',
    'volunteer'       => 'VolunteerRoutes.php',
    'public'          => 'PublicRoutes.php',
];

if (!isset($routes[$section])) {
    ResponseHelper::notFound('Endpoint not found');
}

$routeFile = __DIR__ . '/routes/' . $routes[$section];

if (!file_exists($routeFile)) {
    Helpers::logError("Missing route file: $routeFile");
    ResponseHelper::serverError('Internal server error');
}

require_once $routeFile;