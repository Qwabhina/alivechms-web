<?php

/**
 * Migration: AddExpenseProofFile
 * 
 * Adds ProofFile column to expense table for storing receipt/invoice uploads
 * 
 * Generated on 2026-01-10
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Database/Migration.php';

class AddExpenseProofFile extends Migration
{
   /**
    * Run the migration
    */
   public function up(): void
   {
      $this->connection->exec("
            ALTER TABLE `expense` 
            ADD COLUMN `ProofFile` VARCHAR(255) NULL DEFAULT NULL 
            COMMENT 'Path to uploaded proof document (receipt/invoice)' 
            AFTER `RequestedBy`
        ");
   }

   /**
    * Reverse the migration
    */
   public function down(): void
   {
      $this->connection->exec("ALTER TABLE `expense` DROP COLUMN `ProofFile`");
   }

   /**
    * Get migration description
    */
   public function getDescription(): string
   {
      return 'Add ProofFile column to expense table for receipt/invoice uploads';
   }
}
