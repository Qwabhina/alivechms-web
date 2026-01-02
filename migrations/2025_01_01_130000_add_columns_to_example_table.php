<?php

/**
 * Migration: AddColumnsToExampleTable
 * 
 * Example migration showing how to modify existing tables
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Database/Migration.php';

class AddColumnsToExampleTable extends Migration
{
   /**
    * Run the migration
    */
   public function up(): void
   {
      $this->schema->table('example_table', function (Blueprint $table) {
         $table->string('phone', 20)->nullable();
         $table->date('birth_date')->nullable();
         $table->timestamp('last_login_at')->nullable();

         // Add index on new phone column
         $table->index(['phone']);
      });
   }

   /**
    * Reverse the migration
    */
   public function down(): void
   {
      $this->schema->table('example_table', function (Blueprint $table) {
         $table->dropIndex('example_table_phone_index');
         $table->dropColumn('phone');
         $table->dropColumn('birth_date');
         $table->dropColumn('last_login_at');
      });
   }

   /**
    * Get migration description
    */
   public function getDescription(): string
   {
      return 'Add phone, birth_date, and last_login_at columns to example table';
   }
}
