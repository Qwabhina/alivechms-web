<?php

/**
 * Simple Migration System Test
 * 
 * Tests basic migration functionality
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Database/MigrationManager.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🧪 Testing Migration System...\n\n";

try {
   // Test 1: Initialize migration manager
   echo "✅ Testing migration manager initialization...\n";
   $database = Database::getInstance();
   $connection = $database->getConnection();
   $migrationManager = new MigrationManager($connection, __DIR__ . '/migrations');
   echo "   ✓ Migration manager initialized\n";

   // Test 2: Check migration status
   echo "✅ Testing migration status...\n";
   $status = $migrationManager->status();
   echo "   ✓ Found " . count($status) . " migration(s)\n";

   foreach ($status as $migration) {
      echo "   - {$migration['migration']}: {$migration['status']}\n";
   }

   // Test 3: Create a test migration
   echo "✅ Testing migration creation...\n";
   $testMigrationPath = $migrationManager->create('test_migration_' . time());
   echo "   ✓ Created migration: " . basename($testMigrationPath) . "\n";

   // Clean up test migration
   if (file_exists($testMigrationPath)) {
      unlink($testMigrationPath);
      echo "   ✓ Cleaned up test migration\n";
   }

   echo "\n🎉 All migration tests passed!\n";
   echo "🚀 Migration system is working correctly!\n";
   echo "\n📝 To run migrations:\n";
   echo "   php migrate.php status    - Check migration status\n";
   echo "   php migrate.php migrate   - Run pending migrations\n";
   echo "   php migrate.php rollback  - Rollback last batch\n";
} catch (Exception $e) {
   echo "\n❌ Test failed: " . $e->getMessage() . "\n";
   echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
   exit(1);
}
