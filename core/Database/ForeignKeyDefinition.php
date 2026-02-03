<?php

declare(strict_types=1);

namespace AliveChMS\Core\Database;

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
