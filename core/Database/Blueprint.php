<?php

declare(strict_types=1);

namespace AliveChMS\Core\Database;

use Exception;

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

   public function id(string $column = 'id'): ColumnDefinition
   {
      return $this->bigInteger($column)->autoIncrement()->primary();
   }

   public function string(string $column, int $length = 255): ColumnDefinition
   {
      return $this->addColumn('VARCHAR', $column, ['length' => $length]);
   }

   public function text(string $column): ColumnDefinition
   {
      return $this->addColumn('TEXT', $column);
   }

   public function integer(string $column): ColumnDefinition
   {
      return $this->addColumn('INT', $column);
   }

   public function bigInteger(string $column): ColumnDefinition
   {
      return $this->addColumn('BIGINT', $column);
   }

   public function decimal(string $column, int $precision = 8, int $scale = 2): ColumnDefinition
   {
      return $this->addColumn('DECIMAL', $column, ['precision' => $precision, 'scale' => $scale]);
   }

   public function boolean(string $column): ColumnDefinition
   {
      return $this->addColumn('TINYINT', $column, ['length' => 1]);
   }

   public function date(string $column): ColumnDefinition
   {
      return $this->addColumn('DATE', $column);
   }

   public function dateTime(string $column): ColumnDefinition
   {
      return $this->addColumn('DATETIME', $column);
   }

   public function timestamp(string $column): ColumnDefinition
   {
      return $this->addColumn('TIMESTAMP', $column);
   }

   public function timestamps(): void
   {
      $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
      $this->timestamp('updated_at')->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
   }

   public function enum(string $column, array $values): ColumnDefinition
   {
      return $this->addColumn('ENUM', $column, ['values' => $values]);
   }

   public function json(string $column): ColumnDefinition
   {
      return $this->addColumn('JSON', $column);
   }

   public function foreignId(string $column): ColumnDefinition
   {
      return $this->bigInteger($column)->unsigned();
   }

   public function index(array $columns, ?string $name = null): void
   {
      $name = $name ?: $this->table . '_' . implode('_', $columns) . '_index';
      $this->indexes[] = ['type' => 'INDEX', 'name' => $name, 'columns' => $columns];
   }

   public function unique(array $columns, ?string $name = null): void
   {
      $name = $name ?: $this->table . '_' . implode('_', $columns) . '_unique';
      $this->indexes[] = ['type' => 'UNIQUE', 'name' => $name, 'columns' => $columns];
   }

   public function foreign(string $column): ForeignKeyDefinition
   {
      return new ForeignKeyDefinition($this, $column);
   }

   public function dropColumn(string $column): void
   {
      $this->commands[] = ['type' => 'dropColumn', 'column' => $column];
   }

   public function dropIndex(string $name): void
   {
      $this->commands[] = ['type' => 'dropIndex', 'name' => $name];
   }

   private function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
   {
      $column = new ColumnDefinition($type, $name, $parameters);
      $this->columns[] = $column;
      return $column;
   }

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

   public function toSqlStatements(): array
   {
      if ($this->action !== 'alter') {
         throw new Exception('toSqlStatements() only works for ALTER operations');
      }
      $statements = [];
      foreach ($this->columns as $column) {
         $statements[] = "ALTER TABLE `{$this->table}` ADD COLUMN " . $column->toSql();
      }
      foreach ($this->indexes as $index) {
         $columns = '`' . implode('`, `', $index['columns']) . '`';
         if ($index['type'] === 'UNIQUE') {
            $statements[] = "ALTER TABLE `{$this->table}` ADD UNIQUE KEY `{$index['name']}` ($columns)";
         } else {
            $statements[] = "ALTER TABLE `{$this->table}` ADD KEY `{$index['name']}` ($columns)";
         }
      }
      foreach ($this->commands as $command) {
         if ($command['type'] === 'dropColumn') {
            $statements[] = "ALTER TABLE `{$this->table}` DROP COLUMN `{$command['column']}`";
         } elseif ($command['type'] === 'dropIndex') {
            $statements[] = "ALTER TABLE `{$this->table}` DROP INDEX `{$command['name']}`";
         }
      }
      return $statements;
   }
}
