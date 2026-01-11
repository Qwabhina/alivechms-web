<?php

/**
 * Database Migration Base Class
 *
 * Base class for all database migrations. Provides a clean interface
 * for defining schema changes with up/down methods.
 *
 * Features:
 * - Schema builder integration
 * - Rollback support
 * - Transaction safety
 * - Timestamp-based ordering
 * - Batch tracking
 *
 * @package  AliveChMS\Core\Database
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

abstract class Migration
{
   protected PDO $connection;
   protected SchemaBuilder $schema;

   public function __construct(PDO $connection)
   {
      $this->connection = $connection;
      $this->schema = new SchemaBuilder($connection);
   }

   /**
    * Run the migration (apply changes)
    */
   abstract public function up(): void;

   /**
    * Reverse the migration (rollback changes)
    */
   abstract public function down(): void;

   /**
    * Get migration description
    */
   public function getDescription(): string
   {
      return 'Database migration';
   }

   /**
    * Execute raw SQL
    */
   protected function execute(string $sql, array $params = []): void
   {
      $stmt = $this->connection->prepare($sql);
      $stmt->execute($params);
   }

   /**
    * Check if table exists
    */
   protected function tableExists(string $table): bool
   {
      $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
      $stmt->execute([$table]);
      return $stmt->rowCount() > 0;
   }

   /**
    * Check if column exists
    */
   protected function columnExists(string $table, string $column): bool
   {
      $stmt = $this->connection->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
      $stmt->execute([$column]);
      return $stmt->rowCount() > 0;
   }

   /**
    * Check if index exists
    */
   protected function indexExists(string $table, string $index): bool
   {
      $stmt = $this->connection->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
      $stmt->execute([$index]);
      return $stmt->rowCount() > 0;
   }

   /**
    * Get table schema
    */
   protected function getTableSchema(string $table): array
   {
      $stmt = $this->connection->prepare("DESCRIBE `$table`");
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }
}
