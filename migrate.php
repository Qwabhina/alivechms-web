<?php

/**
 * Migration Command Line Interface
 *
 * Provides command-line access to database migration operations.
 * 
 * Usage:
 *   php migrate.php migrate          - Run pending migrations
 *   php migrate.php rollback         - Rollback last batch
 *   php migrate.php rollback --steps=3  - Rollback 3 batches
 *   php migrate.php status           - Show migration status
 *   php migrate.php create table_name   - Create new migration
 *
 * @package  AliveChMS
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

// Block web access
if (php_sapi_name() !== 'cli') {
   die('This script can only be run from the command line.');
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Database/MigrationManager.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Colors for CLI output
class CliColors
{
   public static function green(string $text): string
   {
      return "\033[32m$text\033[0m";
   }

   public static function red(string $text): string
   {
      return "\033[31m$text\033[0m";
   }

   public static function yellow(string $text): string
   {
      return "\033[33m$text\033[0m";
   }

   public static function blue(string $text): string
   {
      return "\033[34m$text\033[0m";
   }
}

function showUsage(): void
{
   echo "AliveChMS Migration Tool\n\n";
   echo "Usage:\n";
   echo "  php migrate.php migrate                 Run pending migrations\n";
   echo "  php migrate.php rollback                Rollback last batch\n";
   echo "  php migrate.php rollback --steps=N      Rollback N batches\n";
   echo "  php migrate.php status                  Show migration status\n";
   echo "  php migrate.php create <name>           Create new migration\n";
   echo "  php migrate.php help                    Show this help\n\n";
}

function parseArguments(array $argv): array
{
   $command = $argv[1] ?? 'help';
   $options = [];
   $arguments = [];

   for ($i = 2; $i < count($argv); $i++) {
      $arg = $argv[$i];

      if (str_starts_with($arg, '--')) {
         // Parse --key=value
         if (str_contains($arg, '=')) {
            [$key, $value] = explode('=', substr($arg, 2), 2);
            $options[$key] = $value;
         } else {
            $options[substr($arg, 2)] = true;
         }
      } else {
         $arguments[] = $arg;
      }
   }

   return [$command, $options, $arguments];
}

try {
   // Parse command line arguments
   [$command, $options, $arguments] = parseArguments($argv);

   // Initialize database connection and migration manager
   $database = Database::getInstance();
   $connection = $database->getConnection();
   $migrationManager = new MigrationManager($connection);

   switch ($command) {
      case 'migrate':
         echo CliColors::blue("Running migrations...\n\n");
         $result = $migrationManager->migrate();

         if (isset($result['executed']) && !empty($result['executed'])) {
            echo CliColors::green("✓ Migrations completed successfully!\n");
            echo "Executed migrations:\n";
            foreach ($result['executed'] as $migration) {
               echo "  - $migration\n";
            }
            echo "\nBatch: {$result['batch']}\n";
         } else {
            echo CliColors::yellow("No pending migrations to run.\n");
         }
         break;

      case 'rollback':
         $steps = (int)($options['steps'] ?? 1);
         echo CliColors::blue("Rolling back $steps batch(es)...\n\n");

         $result = $migrationManager->rollback($steps);

         if (isset($result['rolled_back']) && !empty($result['rolled_back'])) {
            echo CliColors::green("✓ Rollback completed successfully!\n");
            echo "Rolled back migrations:\n";
            foreach ($result['rolled_back'] as $migration) {
               echo "  - $migration\n";
            }
         } else {
            echo CliColors::yellow("No migrations to rollback.\n");
         }
         break;

      case 'status':
         echo CliColors::blue("Migration Status:\n\n");
         $status = $migrationManager->status();

         if (empty($status)) {
            echo CliColors::yellow("No migrations found.\n");
            break;
         }

         // Table header
         printf("%-50s %-10s %-5s %-20s\n", 'Migration', 'Status', 'Batch', 'Executed At');
         echo str_repeat('-', 90) . "\n";

         foreach ($status as $migration) {
            $statusColor = $migration['status'] === 'Executed' ? 'green' : 'yellow';
            $statusText = CliColors::$statusColor($migration['status']);

            printf(
               "%-50s %-20s %-5s %-20s\n",
               $migration['migration'],
               $statusText,
               $migration['batch'] ?? '-',
               $migration['executed_at'] ?? '-'
            );
         }
         break;

      case 'create':
         if (empty($arguments)) {
            echo CliColors::red("Error: Migration name is required.\n");
            echo "Usage: php migrate.php create <migration_name>\n";
            exit(1);
         }

         $name = $arguments[0];
         echo CliColors::blue("Creating migration: $name\n");

         $filepath = $migrationManager->create($name);
         echo CliColors::green("✓ Migration created: $filepath\n");
         break;

      case 'help':
      default:
         showUsage();
         break;
   }
} catch (Exception $e) {
   echo CliColors::red("Error: " . $e->getMessage() . "\n");
   exit(1);
}
