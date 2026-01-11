<?php

/**
 * Query Builder
 *
 * Fluent interface for building complex database queries.
 * Provides a more readable and maintainable way to construct SQL queries.
 *
 * @package  AliveChMS\Core\Database
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../ORM.php';

class QueryBuilder
{
   private ORM $orm;
   private string $table = '';
   private array $select = ['*'];
   private array $joins = [];
   private array $where = [];
   private array $params = [];
   private array $orderBy = [];
   private array $groupBy = [];
   private ?int $limit = null;
   private ?int $offset = null;

   public function __construct(?ORM $orm = null)
   {
      $this->orm = $orm ?? new ORM();
   }

   /**
    * Set the table to query from
    *
    * @param string $table Table name (with optional alias)
    * @return self
    */
   public function _table(string $table): self
   {
      $this->table = $table;
      return $this;
   }

   /**
    * Set the columns to select
    *
    * @param array|string $columns Columns to select
    * @return self
    */
   public function select($columns = ['*']): self
   {
      $this->select = is_array($columns) ? $columns : [$columns];
      return $this;
   }

   /**
    * Add a JOIN clause
    *
    * @param string $table Table to join
    * @param string $on Join condition
    * @param string $type Join type (INNER, LEFT, RIGHT)
    * @return self
    */
   public function join(string $table, string $on, string $type = 'INNER'): self
   {
      $this->joins[] = [
         'table' => $table,
         'on' => $on,
         'type' => strtoupper($type)
      ];
      return $this;
   }

   /**
    * Add a LEFT JOIN clause
    *
    * @param string $table Table to join
    * @param string $on Join condition
    * @return self
    */
   public function leftJoin(string $table, string $on): self
   {
      return $this->join($table, $on, 'LEFT');
   }

   /**
    * Add a WHERE clause
    *
    * @param string $column Column name
    * @param mixed $value Value to compare
    * @param string $operator Comparison operator
    * @return self
    */
   public function where(string $column, $value, string $operator = '='): self
   {
      $paramKey = 'param_' . count($this->params);
      $this->where[] = "$column $operator :$paramKey";
      $this->params[$paramKey] = $value;
      return $this;
   }

   /**
    * Add a WHERE IN clause
    *
    * @param string $column Column name
    * @param array $values Values for IN clause
    * @return self
    */
   public function whereIn(string $column, array $values): self
   {
      $placeholders = [];
      foreach ($values as $i => $value) {
         $paramKey = 'in_param_' . count($this->params) . '_' . $i;
         $placeholders[] = ":$paramKey";
         $this->params[$paramKey] = $value;
      }
      $this->where[] = "$column IN (" . implode(', ', $placeholders) . ")";
      return $this;
   }

   /**
    * Add a WHERE BETWEEN clause
    *
    * @param string $column Column name
    * @param mixed $start Start value
    * @param mixed $end End value
    * @return self
    */
   public function whereBetween(string $column, $start, $end): self
   {
      $startKey = 'between_start_' . count($this->params);
      $endKey = 'between_end_' . count($this->params);
      $this->where[] = "$column BETWEEN :$startKey AND :$endKey";
      $this->params[$startKey] = $start;
      $this->params[$endKey] = $end;
      return $this;
   }

   /**
    * Add a WHERE LIKE clause
    *
    * @param string $column Column name
    * @param string $pattern LIKE pattern
    * @return self
    */
   public function whereLike(string $column, string $pattern): self
   {
      return $this->where($column, $pattern, 'LIKE');
   }

   /**
    * Add a WHERE IS NULL clause
    *
    * @param string $column Column name
    * @return self
    */
   public function whereNull(string $column): self
   {
      $this->where[] = "$column IS NULL";
      return $this;
   }

   /**
    * Add a WHERE IS NOT NULL clause
    *
    * @param string $column Column name
    * @return self
    */
   public function whereNotNull(string $column): self
   {
      $this->where[] = "$column IS NOT NULL";
      return $this;
   }

   /**
    * Add an ORDER BY clause
    *
    * @param string $column Column name
    * @param string $direction Sort direction (ASC, DESC)
    * @return self
    */
   public function orderBy(string $column, string $direction = 'ASC'): self
   {
      $this->orderBy[$column] = strtoupper($direction);
      return $this;
   }

   /**
    * Add a GROUP BY clause
    *
    * @param string $column Column name
    * @return self
    */
   public function groupBy(string $column): self
   {
      $this->groupBy[] = $column;
      return $this;
   }

   /**
    * Set LIMIT clause
    *
    * @param int $limit Number of records to limit
    * @return self
    */
   public function limit(int $limit): self
   {
      $this->limit = $limit;
      return $this;
   }

   /**
    * Set OFFSET clause
    *
    * @param int $offset Number of records to skip
    * @return self
    */
   public function offset(int $offset): self
   {
      $this->offset = $offset;
      return $this;
   }

   /**
    * Set pagination
    *
    * @param int $page Page number (1-based)
    * @param int $perPage Records per page
    * @return self
    */
   public function paginate(int $page, int $perPage): self
   {
      $this->limit = $perPage;
      $this->offset = ($page - 1) * $perPage;
      return $this;
   }

   /**
    * Execute the query and return results
    *
    * @return array Query results
    */
   public function get(): array
   {
      return $this->orm->selectWithJoin(
         $this->table,
         $this->joins,
         $this->select,
         $this->buildConditions(),
         $this->params,
         $this->orderBy,
         $this->groupBy,
         $this->limit ?? 0,
         $this->offset ?? 0
      );
   }

   /**
    * Get the first result
    *
    * @return array|null First result or null
    */
   public function first(): ?array
   {
      $results = $this->limit(1)->get();
      return $results[0] ?? null;
   }

   /**
    * Get count of matching records
    *
    * @return int Record count
    */
   public function count(): int
   {
      $originalSelect = $this->select;
      $this->select = ['COUNT(*) as count'];

      $result = $this->get();
      $count = (int)($result[0]['count'] ?? 0);

      // Restore original select
      $this->select = $originalSelect;

      return $count;
   }

   /**
    * Execute a raw query
    *
    * @param string $sql SQL query
    * @param array $params Query parameters
    * @return array Query results
    */
   public function raw(string $sql, array $params = []): array
   {
      return $this->orm->runQuery($sql, $params);
   }

   /**
    * Build conditions array for ORM
    *
    * @return array Conditions array
    */
   private function buildConditions(): array
   {
      $conditions = [];
      foreach ($this->where as $i => $condition) {
         $conditions["condition_$i"] = $condition;
      }
      return $conditions;
   }

   /**
    * Get the built SQL query (for debugging)
    *
    * @return string SQL query
    */
   public function toSql(): string
   {
      $sql = "SELECT " . implode(', ', $this->select) . " FROM " . $this->table;

      foreach ($this->joins as $join) {
         $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['on']}";
      }

      if (!empty($this->where)) {
         $sql .= " WHERE " . implode(' AND ', $this->where);
      }

      if (!empty($this->groupBy)) {
         $sql .= " GROUP BY " . implode(', ', $this->groupBy);
      }

      if (!empty($this->orderBy)) {
         $orderClauses = [];
         foreach ($this->orderBy as $column => $direction) {
            $orderClauses[] = "$column $direction";
         }
         $sql .= " ORDER BY " . implode(', ', $orderClauses);
      }

      if ($this->limit) {
         $sql .= " LIMIT " . $this->limit;
      }

      if ($this->offset) {
         $sql .= " OFFSET " . $this->offset;
      }

      return $sql;
   }

   /**
    * Static factory method
    *
    * @param string $table Table name
    * @return self
    */
   public static function table(string $table): self
   {
      return (new self())->_table($table);
   }
}
