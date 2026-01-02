<?php

/**
 * Migration: CreateExampleTable
 * 
 * Example migration demonstrating the migration system
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Database/Migration.php';

class CreateExampleTable extends Migration
{
   /**
    * Run the migration
    */
   public function up(): void
   {
      $this->schema->create('example_table', function (Blueprint $table) {
         $table->id();
         $table->string('name', 100);
         $table->string('email')->unique();
         $table->text('description')->nullable();
         $table->integer('age')->nullable();
         $table->boolean('is_active')->default(1);
         $table->decimal('price', 10, 2)->nullable();
         $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
         $table->json('metadata')->nullable();
         $table->timestamps();
      });

      // Add indexes
      $this->schema->table('example_table', function (Blueprint $table) {
         $table->index(['name', 'status']);
         $table->index(['created_at']);
      });
   }

   /**
    * Reverse the migration
    */
   public function down(): void
   {
      $this->schema->drop('example_table');
   }

   /**
    * Get migration description
    */
   public function getDescription(): string
   {
      return 'Create example table with various column types';
   }
}
