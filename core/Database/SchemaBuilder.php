<?php

declare(strict_types=1);

namespace AliveChMS\Core\Database;

use PDO;

/**
 * Database Schema Builder
 */
class SchemaBuilder
{
   private PDO $connection;

   public function __construct(PDO $connection)
   {
      $this->connection = $connection;
   }

   public function create(string $table, callable $callback): void
   {
      $blueprint = new Blueprint($table, 'create');
      $callback($blueprint);
      $sql = $blueprint->toSql();
      $this->connection->exec($sql);
   }

   public function table(string $table, callable $callback): void
   {
      $blueprint = new Blueprint($table, 'alter');
      $callback($blueprint);
      $statements = $blueprint->toSqlStatements();
      foreach ($statements as $sql) {
         $this->connection->exec($sql);
      }
   }

   public function drop(string $table): void
   {
      $sql = "DROP TABLE IF EXISTS `$table`";
      $this->connection->exec($sql);
   }

   public function rename(string $from, string $to): void
   {
      $sql = "RENAME TABLE `$from` TO `$to`";
      $this->connection->exec($sql);
   }

   public function hasTable(string $table): bool
   {
      $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
      $stmt->execute([$table]);
      return $stmt->rowCount() > 0;
   }

   public function hasColumn(string $table, string $column): bool
   {
      $stmt = $this->connection->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
      $stmt->execute([$column]);
      return $stmt->rowCount() > 0;
   }
}
