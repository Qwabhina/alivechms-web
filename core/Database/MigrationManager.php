<?php

/**
 * Migration Manager
 *
 * Manages database migrations - running, rolling back, and tracking
 * migration state. Provides commands for migration operations.
 *
 * Features:
 * - Run pending migrations
 * - Rollback migrations
 * - Migration status tracking
 * - Batch management
 * - Transaction safety
 *
 * @package  AliveChMS\Core\Database
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-January
 */

declare(strict_types=1);

require_once __DIR__ . '/Migration.php';
require_once __DIR__ . '/SchemaBuilder.php';

class MigrationManager
{
   private PDO $connection;
   private string $migrationsPath;
   private string $migrationsTable = 'migrations';

   public function __construct(PDO $connection, string $migrationsPath = null)
   {
      $this->connection = $connection;
      $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../migrations';
      $this->ensureMigrationsTable();
   }

   /**
    * Create migrations table if it doesn't exist
    */
   private function ensureMigrationsTable(): void
   {
      $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch` int(11) NOT NULL,
            `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

      $this->connection->exec($sql);
   }

   /**
    * Run pending migrations
    */
   public function migrate(): array
   {
      $pendingMigrations = $this->getPendingMigrations();

      if (empty($pendingMigrations)) {
         return ['message' => 'No pending migrations'];
      }

      $batch = $this->getNextBatchNumber();
      $executed = [];

      $this->connection->beginTransaction();

      try {
         foreach ($pendingMigrations as $migrationFile) {
            $migrationName = $this->getMigrationName($migrationFile);

            echo "Migrating: $migrationName\n";

            $migration = $this->loadMigration($migrationFile);
            $migration->up();

            $this->recordMigration($migrationName, $batch);
            $executed[] = $migrationName;

            echo "Migrated: $migrationName\n";
         }

         $this->connection->commit();

         return [
            'message' => 'Migrations completed successfully',
            'executed' => $executed,
            'batch' => $batch
         ];
      } catch (Exception $e) {
         $this->connection->rollBack();
         throw new Exception("Migration failed: " . $e->getMessage());
      }
   }

   /**
    * Rollback migrations
    */
   public function rollback(int $steps = 1): array
   {
      $batches = $this->getExecutedBatches($steps);

      if (empty($batches)) {
         return ['message' => 'No migrations to rollback'];
      }

      $rolledBack = [];

      $this->connection->beginTransaction();

      try {
         foreach ($batches as $batch) {
            $migrations = $this->getMigrationsInBatch($batch);

            // Rollback in reverse order
            foreach (array_reverse($migrations) as $migrationRecord) {
               $migrationName = $migrationRecord['migration'];

               echo "Rolling back: $migrationName\n";

               $migrationFile = $this->findMigrationFile($migrationName);
               if ($migrationFile) {
                  $migration = $this->loadMigration($migrationFile);
                  $migration->down();
               }

               $this->removeMigrationRecord($migrationName);
               $rolledBack[] = $migrationName;

               echo "Rolled back: $migrationName\n";
            }
         }

         $this->connection->commit();

         return [
            'message' => 'Rollback completed successfully',
            'rolled_back' => $rolledBack
         ];
      } catch (Exception $e) {
         $this->connection->rollBack();
         throw new Exception("Rollback failed: " . $e->getMessage());
      }
   }

   /**
    * Get migration status
    */
   public function status(): array
   {
      $allMigrations = $this->getAllMigrationFiles();
      $executedMigrations = $this->getExecutedMigrations();

      $status = [];

      foreach ($allMigrations as $file) {
         $name = $this->getMigrationName($file);
         $executed = isset($executedMigrations[$name]);

         $status[] = [
            'migration' => $name,
            'status' => $executed ? 'Executed' : 'Pending',
            'batch' => $executed ? $executedMigrations[$name]['batch'] : null,
            'executed_at' => $executed ? $executedMigrations[$name]['executed_at'] : null
         ];
      }

      return $status;
   }

   /**
    * Create a new migration file
    */
   public function create(string $name): string
   {
      $timestamp = date('Y_m_d_His');
      $className = $this->studlyCase($name);
      $filename = "{$timestamp}_{$name}.php";
      $filepath = $this->migrationsPath . '/' . $filename;

      if (!is_dir($this->migrationsPath)) {
         mkdir($this->migrationsPath, 0755, true);
      }

      $stub = $this->getMigrationStub($className);
      file_put_contents($filepath, $stub);

      return $filepath;
   }

   /**
    * Get pending migrations
    */
   private function getPendingMigrations(): array
   {
      $allMigrations = $this->getAllMigrationFiles();
      $executedMigrations = array_keys($this->getExecutedMigrations());

      return array_filter($allMigrations, function ($file) use ($executedMigrations) {
         $name = $this->getMigrationName($file);
         return !in_array($name, $executedMigrations);
      });
   }

   /**
    * Get all migration files
    */
   private function getAllMigrationFiles(): array
   {
      if (!is_dir($this->migrationsPath)) {
         return [];
      }

      $files = glob($this->migrationsPath . '/*.php');
      sort($files);

      return $files;
   }

   /**
    * Get executed migrations
    */
   private function getExecutedMigrations(): array
   {
      $stmt = $this->connection->prepare("SELECT migration, batch, executed_at FROM {$this->migrationsTable} ORDER BY id");
      $stmt->execute();

      $migrations = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $migrations[$row['migration']] = $row;
      }

      return $migrations;
   }

   /**
    * Get next batch number
    */
   private function getNextBatchNumber(): int
   {
      $stmt = $this->connection->prepare("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      return ($result['max_batch'] ?? 0) + 1;
   }

   /**
    * Get executed batches for rollback
    */
   private function getExecutedBatches(int $steps): array
   {
      $stmt = $this->connection->prepare("SELECT DISTINCT batch FROM {$this->migrationsTable} ORDER BY batch DESC LIMIT ?");
      $stmt->execute([$steps]);

      return $stmt->fetchAll(PDO::FETCH_COLUMN);
   }

   /**
    * Get migrations in batch
    */
   private function getMigrationsInBatch(int $batch): array
   {
      $stmt = $this->connection->prepare("SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id");
      $stmt->execute([$batch]);

      return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /**
    * Record migration execution
    */
   private function recordMigration(string $migration, int $batch): void
   {
      $stmt = $this->connection->prepare("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)");
      $stmt->execute([$migration, $batch]);
   }

   /**
    * Remove migration record
    */
   private function removeMigrationRecord(string $migration): void
   {
      $stmt = $this->connection->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
      $stmt->execute([$migration]);
   }

   /**
    * Load migration instance
    */
   private function loadMigration(string $file): Migration
   {
      require_once $file;

      $className = $this->getMigrationClassName($file);

      if (!class_exists($className)) {
         throw new Exception("Migration class $className not found in $file");
      }

      return new $className($this->connection);
   }

   /**
    * Get migration name from file
    */
   private function getMigrationName(string $file): string
   {
      return basename($file, '.php');
   }

   /**
    * Get migration class name from file
    */
   private function getMigrationClassName(string $file): string
   {
      $name = $this->getMigrationName($file);
      // Remove timestamp prefix (YYYY_MM_DD_HHMMSS_)
      $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $name);
      return $this->studlyCase($name);
   }

   /**
    * Find migration file by name
    */
   private function findMigrationFile(string $migrationName): ?string
   {
      $files = $this->getAllMigrationFiles();

      foreach ($files as $file) {
         if ($this->getMigrationName($file) === $migrationName) {
            return $file;
         }
      }

      return null;
   }

   /**
    * Convert string to StudlyCase
    */
   private function studlyCase(string $string): string
   {
      return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
   }

   /**
    * Get migration stub template
    */
   private function getMigrationStub(string $className): string
   {
      return "<?php

/**
 * Migration: $className
 * 
 * Generated on " . date('Y-m-d H:i:s') . "
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Database/Migration.php';

class $className extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Add your migration logic here
        // Example:
        // \$this->schema->create('example_table', function (Blueprint \$table) {
        //     \$table->id();
        //     \$table->string('name');
        //     \$table->timestamps();
        // });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // Add your rollback logic here
        // Example:
        // \$this->schema->drop('example_table');
    }

    /**
     * Get migration description
     */
    public function getDescription(): string
    {
        return 'Migration: $className';
    }
}
";
   }
}
