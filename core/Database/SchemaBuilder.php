<?php

/**
 * Database Schema Builder
 *
 * Provides a fluent interface for building database schema changes.
 * Supports table creation, modification, and deletion with a clean API.
 *
 * @package  AliveChMS\Core\Database
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

class SchemaBuilder
{
   private PDO $connection;

   public function __construct(PDO $connection)
   {
      $this->connection = $connection;
   }

   /**
    * Create a new table
    */
   public function create(string $table, callable $callback): void
   {
      $blueprint = new Blueprint($table, 'create');
      $callback($blueprint);

      $sql = $blueprint->toSql();
      $this->connection->exec($sql);
   }

   /**
    * Modify an existing table
    */
   public function table(string $table, callable $callback): void
   {
      $blueprint = new Blueprint($table, 'alter');
      $callback($blueprint);

      $statements = $blueprint->toSqlStatements();
      foreach ($statements as $sql) {
         $this->connection->exec($sql);
      }
   }

   /**
    * Drop a table
    */
   public function drop(string $table): void
   {
      $sql = "DROP TABLE IF EXISTS `$table`";
      $this->connection->exec($sql);
   }

   /**
    * Rename a table
    */
   public function rename(string $from, string $to): void
   {
      $sql = "RENAME TABLE `$from` TO `$to`";
      $this->connection->exec($sql);
   }

   /**
    * Check if table exists
    */
   public function hasTable(string $table): bool
   {
      $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
      $stmt->execute([$table]);
      return $stmt->rowCount() > 0;
   }

   /**
    * Check if column exists
    */
   public function hasColumn(string $table, string $column): bool
   {
      $stmt = $this->connection->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
      $stmt->execute([$column]);
      return $stmt->rowCount() > 0;
   }
}

/**
 * Blueprint for building table schema
 */
class Blueprint
{
   private string $table;
   private string $action;
   private array $columns = [];
   private array $indexes = [];
   private array $commands = [];

   public function __construct(string $table, string $action = 'create')
   {
      $this->table = $table;
      $this->action = $action;
   }

   /**
    * Add auto-incrementing primary key
    */
   public function id(string $column = 'id'): ColumnDefinition
   {
      return $this->bigInteger($column)->autoIncrement()->primary();
   }

   /**
    * Add string column
    */
   public function string(string $column, int $length = 255): ColumnDefinition
   {
      return $this->addColumn('VARCHAR', $column, ['length' => $length]);
   }

   /**
    * Add text column
    */
   public function text(string $column): ColumnDefinition
   {
      return $this->addColumn('TEXT', $column);
   }

   /**
    * Add integer column
    */
   public function integer(string $column): ColumnDefinition
   {
      return $this->addColumn('INT', $column);
   }

   /**
    * Add big integer column
    */
   public function bigInteger(string $column): ColumnDefinition
   {
      return $this->addColumn('BIGINT', $column);
   }

   /**
    * Add decimal column
    */
   public function decimal(string $column, int $precision = 8, int $scale = 2): ColumnDefinition
   {
      return $this->addColumn('DECIMAL', $column, ['precision' => $precision, 'scale' => $scale]);
   }

   /**
    * Add boolean column
    */
   public function boolean(string $column): ColumnDefinition
   {
      return $this->addColumn('TINYINT', $column, ['length' => 1]);
   }

   /**
    * Add date column
    */
   public function date(string $column): ColumnDefinition
   {
      return $this->addColumn('DATE', $column);
   }

   /**
    * Add datetime column
    */
   public function dateTime(string $column): ColumnDefinition
   {
      return $this->addColumn('DATETIME', $column);
   }

   /**
    * Add timestamp column
    */
   public function timestamp(string $column): ColumnDefinition
   {
      return $this->addColumn('TIMESTAMP', $column);
   }

   /**
    * Add timestamps (created_at, updated_at)
    */
   public function timestamps(): void
   {
      $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
      $this->timestamp('updated_at')->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
   }

   /**
    * Add enum column
    */
   public function enum(string $column, array $values): ColumnDefinition
   {
      return $this->addColumn('ENUM', $column, ['values' => $values]);
   }

   /**
    * Add JSON column
    */
   public function json(string $column): ColumnDefinition
   {
      return $this->addColumn('JSON', $column);
   }

   /**
    * Add foreign key column
    */
   public function foreignId(string $column): ColumnDefinition
   {
      return $this->bigInteger($column)->unsigned();
   }

   /**
    * Add index
    */
   public function index(array $columns, string $name = null): void
   {
      $name = $name ?: $this->table . '_' . implode('_', $columns) . '_index';
      $this->indexes[] = [
         'type' => 'INDEX',
         'name' => $name,
         'columns' => $columns
      ];
   }

   /**
    * Add unique index
    */
   public function unique(array $columns, string $name = null): void
   {
      $name = $name ?: $this->table . '_' . implode('_', $columns) . '_unique';
      $this->indexes[] = [
         'type' => 'UNIQUE',
         'name' => $name,
         'columns' => $columns
      ];
   }

   /**
    * Add foreign key constraint
    */
   public function foreign(string $column): ForeignKeyDefinition
   {
      return new ForeignKeyDefinition($this, $column);
   }

   /**
    * Drop column
    */
   public function dropColumn(string $column): void
   {
      $this->commands[] = ['type' => 'dropColumn', 'column' => $column];
   }

   /**
    * Drop index
    */
   public function dropIndex(string $name): void
   {
      $this->commands[] = ['type' => 'dropIndex', 'name' => $name];
   }

   /**
    * Add column definition
    */
   private function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
   {
      $column = new ColumnDefinition($type, $name, $parameters);
      $this->columns[] = $column;
      return $column;
   }

   /**
    * Generate CREATE TABLE SQL
    */
   public function toSql(): string
   {
      if ($this->action !== 'create') {
         throw new Exception('toSql() only works for CREATE operations');
      }

      $sql = "CREATE TABLE `{$this->table}` (\n";

      $columnDefinitions = [];
      foreach ($this->columns as $column) {
         $columnDefinitions[] = '  ' . $column->toSql();
      }

      $sql .= implode(",\n", $columnDefinitions);

      // Add indexes
      foreach ($this->indexes as $index) {
         $columns = '`' . implode('`, `', $index['columns']) . '`';
         if ($index['type'] === 'UNIQUE') {
            $sql .= ",\n  UNIQUE KEY `{$index['name']}` ($columns)";
         } else {
            $sql .= ",\n  KEY `{$index['name']}` ($columns)";
         }
      }

      $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

      return $sql;
   }

   /**
    * Generate ALTER TABLE SQL statements
    */
   public function toSqlStatements(): array
   {
      if ($this->action !== 'alter') {
         throw new Exception('toSqlStatements() only works for ALTER operations');
      }

      $statements = [];

      // Add columns
      foreach ($this->columns as $column) {
         $statements[] = "ALTER TABLE `{$this->table}` ADD COLUMN " . $column->toSql();
      }

      // Add indexes
      foreach ($this->indexes as $index) {
         $columns = '`' . implode('`, `', $index['columns']) . '`';
         if ($index['type'] === 'UNIQUE') {
            $statements[] = "ALTER TABLE `{$this->table}` ADD UNIQUE KEY `{$index['name']}` ($columns)";
         } else {
            $statements[] = "ALTER TABLE `{$this->table}` ADD KEY `{$index['name']}` ($columns)";
         }
      }

      // Handle commands
      foreach ($this->commands as $command) {
         switch ($command['type']) {
            case 'dropColumn':
               $statements[] = "ALTER TABLE `{$this->table}` DROP COLUMN `{$command['column']}`";
               break;
            case 'dropIndex':
               $statements[] = "ALTER TABLE `{$this->table}` DROP INDEX `{$command['name']}`";
               break;
         }
      }

      return $statements;
   }
}

/**
 * Column definition builder
 */
class ColumnDefinition
{
   private string $type;
   private string $name;
   private array $parameters;
   private array $modifiers = [];

   public function __construct(string $type, string $name, array $parameters = [])
   {
      $this->type = $type;
      $this->name = $name;
      $this->parameters = $parameters;
   }

   public function nullable(): self
   {
      $this->modifiers['nullable'] = true;
      return $this;
   }

   public function default($value): self
   {
      $this->modifiers['default'] = $value;
      return $this;
   }

   public function autoIncrement(): self
   {
      $this->modifiers['autoIncrement'] = true;
      return $this;
   }

   public function primary(): self
   {
      $this->modifiers['primary'] = true;
      return $this;
   }

   public function unsigned(): self
   {
      $this->modifiers['unsigned'] = true;
      return $this;
   }

   public function onUpdate(string $value): self
   {
      $this->modifiers['onUpdate'] = $value;
      return $this;
   }

   public function toSql(): string
   {
      $sql = "`{$this->name}` {$this->type}";

      // Add type parameters
      if (isset($this->parameters['length'])) {
         $sql .= "({$this->parameters['length']})";
      } elseif (isset($this->parameters['precision'], $this->parameters['scale'])) {
         $sql .= "({$this->parameters['precision']},{$this->parameters['scale']})";
      } elseif (isset($this->parameters['values'])) {
         $values = "'" . implode("','", $this->parameters['values']) . "'";
         $sql .= "($values)";
      }

      // Add modifiers
      if (isset($this->modifiers['unsigned'])) {
         $sql .= ' UNSIGNED';
      }

      if (isset($this->modifiers['nullable']) && $this->modifiers['nullable']) {
         $sql .= ' NULL';
      } else {
         $sql .= ' NOT NULL';
      }

      if (isset($this->modifiers['default'])) {
         $default = $this->modifiers['default'];
         if (is_string($default) && $default !== 'CURRENT_TIMESTAMP') {
            $sql .= " DEFAULT '$default'";
         } else {
            $sql .= " DEFAULT $default";
         }
      }

      if (isset($this->modifiers['onUpdate'])) {
         $sql .= " ON UPDATE {$this->modifiers['onUpdate']}";
      }

      if (isset($this->modifiers['autoIncrement'])) {
         $sql .= ' AUTO_INCREMENT';
      }

      if (isset($this->modifiers['primary'])) {
         $sql .= ' PRIMARY KEY';
      }

      return $sql;
   }
}

/**
 * Foreign key definition builder
 */
class ForeignKeyDefinition
{
   private Blueprint $blueprint;
   private string $column;
   private string $references;
   private string $on;
   private string $onDelete = 'RESTRICT';
   private string $onUpdate = 'RESTRICT';

   public function __construct(Blueprint $blueprint, string $column)
   {
      $this->blueprint = $blueprint;
      $this->column = $column;
   }

   public function references(string $column): self
   {
      $this->references = $column;
      return $this;
   }

   public function on(string $table): self
   {
      $this->on = $table;
      return $this;
   }

   public function onDelete(string $action): self
   {
      $this->onDelete = $action;
      return $this;
   }

   public function onUpdate(string $action): self
   {
      $this->onUpdate = $action;
      return $this;
   }

   public function cascadeOnDelete(): self
   {
      return $this->onDelete('CASCADE');
   }

   public function nullOnDelete(): self
   {
      return $this->onDelete('SET NULL');
   }
}
