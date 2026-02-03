<?php

declare(strict_types=1);

namespace AliveChMS\Core\Database;

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
      if (isset($this->parameters['length'])) {
         $sql .= "({$this->parameters['length']})";
      } elseif (isset($this->parameters['precision'], $this->parameters['scale'])) {
         $sql .= "({$this->parameters['precision']},{$this->parameters['scale']})";
      } elseif (isset($this->parameters['values'])) {
         $values = "'" . implode("','", $this->parameters['values']) . "'";
         $sql .= "($values)";
      }
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
