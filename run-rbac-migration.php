<?php
/**
 * RBAC Security Fixes - Database Migration Runner
 * 
 * Runs the permission audit table migration
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/Database.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== RBAC Security Fixes - Database Migration ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "1. Reading migration file...\n";
    $sql = file_get_contents(__DIR__ . '/migrations/create_permission_audit_table.sql');
    
    if (!$sql) {
        throw new Exception('Failed to read migration file');
    }
    
    echo "2. Executing migration...\n";
    $db->exec($sql);
    
    echo "3. Verifying table creation...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'permission_audit'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "   ✓ Table 'permission_audit' created successfully\n";
    } else {
        throw new Exception('Table creation verification failed');
    }
    
    echo "\n4. Checking table structure...\n";
    $stmt = $db->query("DESCRIBE permission_audit");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "   Columns: " . implode(', ', $columns) . "\n";
    
    echo "\n✅ Migration completed successfully!\n\n";
    echo "Next steps:\n";
    echo "1. Clear cache: php -r \"require 'core/Cache.php'; Cache::flush();\"\n";
    echo "2. Test the audit trail by creating/updating a role\n";
    echo "3. View audit logs: SELECT * FROM permission_audit ORDER BY CreatedAt DESC LIMIT 10;\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
