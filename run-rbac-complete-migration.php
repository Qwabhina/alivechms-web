<?php

/**
 * RBAC Complete System Migration Runner
 */

declare(strict_types=1);

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
   $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   foreach ($lines as $line) {
      if (strpos(trim($line), '#') === 0) continue;
      if (strpos($line, '=') === false) continue;
      list($key, $value) = explode('=', $line, 2);
      $_ENV[trim($key)] = trim($value);
   }
}

echo "=================================================================\n";
echo "RBAC COMPLETE SYSTEM MIGRATION\n";
echo "=================================================================\n\n";

try {
   // Direct PDO connection
   $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
   $dbname = $_ENV['DB_NAME'] ?? 'alivechms';
   $user = $_ENV['DB_USER'] ?? 'root';
   $pass = $_ENV['DB_PASS'] ?? '';
   $charset = 'utf8mb4';

   $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
   $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
   ];

   echo "Connecting to database $dbname@$host...\n";
   $db = new PDO($dsn, $user, $pass, $options);
   echo "✓ Connected successfully\n\n";

   echo "Reading migration file...\n";
   $sql = file_get_contents(__DIR__ . '/migrations/rbac_complete_system.sql');

   if ($sql === false) {
      throw new Exception('Failed to read migration file');
   }

   echo "✓ Migration file loaded\n\n";
   echo "Executing migration (this may take a moment)...\n\n";

   // Execute the migration
   $db->exec($sql);

   echo "\n=================================================================\n";
   echo "✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
   echo "=================================================================\n\n";

   // Verify installation
   echo "Verifying installation...\n\n";

   // Check tables
   $tables = $db->query("
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME IN ('permission_category', 'permission_cache', 'permission_group', 'permission_audit')
        ORDER BY TABLE_NAME
    ")->fetchAll(PDO::FETCH_COLUMN);

   echo "New tables created: " . count($tables) . "\n";
   foreach ($tables as $table) {
      echo "  ✓ $table\n";
   }
   echo "\n";

   // Check permissions
   $permCount = $db->query("SELECT COUNT(*) FROM permission")->fetchColumn();
   echo "Permissions in system: $permCount\n\n";

   // Check roles
   $roles = $db->query("SELECT RoleName, DisplayOrder FROM churchrole ORDER BY DisplayOrder")->fetchAll(PDO::FETCH_ASSOC);
   echo "Roles configured: " . count($roles) . "\n";
   foreach ($roles as $role) {
      echo "  ✓ {$role['RoleName']} (Order: {$role['DisplayOrder']})\n";
   }
   echo "\n";

   // Check hierarchy
   $hierarchyCount = $db->query("SELECT COUNT(*) FROM role_hierarchy")->fetchColumn();
   echo "Role hierarchy relationships: $hierarchyCount\n\n";

   // Check views
   $views = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);
   echo "Views created: " . count($views) . "\n";
   foreach ($views as $view) {
      echo "  ✓ $view\n";
   }
   echo "\n";

   // Check stored procedures
   $procedures = $db->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE()")->fetchAll(PDO::FETCH_ASSOC);
   echo "Stored procedures created: " . count($procedures) . "\n";
   foreach ($procedures as $proc) {
      echo "  ✓ {$proc['Name']}\n";
   }
   echo "\n";

   // Check triggers
   $triggers = $db->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC);
   echo "Triggers created: " . count($triggers) . "\n";
   foreach ($triggers as $trigger) {
      echo "  ✓ {$trigger['Trigger']} on {$trigger['Table']}\n";
   }
   echo "\n";

   echo "=================================================================\n";
   echo "NEXT STEPS:\n";
   echo "=================================================================\n";
   echo "1. Update Role.php to use new RBAC class\n";
   echo "2. Update Permission.php for category support\n";
   echo "3. Update RoleRoutes.php for temporal assignments\n";
   echo "4. Update frontend auth.js to use server permissions\n";
   echo "5. Test permission inheritance and caching\n";
   echo "=================================================================\n";
} catch (PDOException $e) {
   echo "\n❌ DATABASE ERROR:\n";
   echo $e->getMessage() . "\n\n";
   echo "Error Code: " . $e->getCode() . "\n";
   exit(1);
} catch (Exception $e) {
   echo "\n❌ ERROR:\n";
   echo $e->getMessage() . "\n\n";
   exit(1);
}
