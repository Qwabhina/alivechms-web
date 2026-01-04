# Database Migration System

## Overview

AliveChMS includes a comprehensive database migration system that provides version control for your database schema. This allows you to safely deploy database changes across different environments.

## Benefits

✅ **Version Control**: Track all database schema changes  
✅ **Rollback Support**: Safely undo migrations if needed  
✅ **Team Collaboration**: Share schema changes via code  
✅ **Environment Consistency**: Same schema across dev/staging/production  
✅ **Batch Tracking**: Group related migrations together  
✅ **Transaction Safety**: All migrations run in transactions

## Quick Start

### 1. Check Migration Status

```bash
php migrate.php status
```

### 2. Create a New Migration

```bash
php migrate.php create create_users_table
```

### 3. Run Pending Migrations

```bash
php migrate.php migrate
```

### 4. Rollback Migrations

```bash
php migrate.php rollback
php migrate.php rollback --steps=3
```

## Migration Structure

### Migration File Format

Migration files follow the naming convention: `YYYY_MM_DD_HHMMSS_description.php`

Example: `2025_01_01_120000_create_users_table.php`

### Basic Migration Template

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database/Migration.php';

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->drop('users');
    }

    public function getDescription(): string
    {
        return 'Create users table';
    }
}
```

## Schema Builder API

### Creating Tables

```php
$this->schema->create('table_name', function (Blueprint $table) {
    // Column definitions go here
});
```

### Modifying Tables

```php
$this->schema->table('table_name', function (Blueprint $table) {
    // Add/modify columns and indexes
});
```

### Dropping Tables

```php
$this->schema->drop('table_name');
```

## Column Types

### Primary Keys

```php
$table->id();                          // Auto-incrementing BIGINT primary key
$table->id('custom_id');               // Custom primary key name
```

### String Columns

```php
$table->string('name');                // VARCHAR(255)
$table->string('name', 100);           // VARCHAR(100)
$table->text('description');           // TEXT
```

### Numeric Columns

```php
$table->integer('age');                // INT
$table->bigInteger('user_id');         // BIGINT
$table->decimal('price', 8, 2);        // DECIMAL(8,2)
$table->boolean('is_active');          // TINYINT(1)
```

### Date/Time Columns

```php
$table->date('birth_date');            // DATE
$table->dateTime('created_at');        // DATETIME
$table->timestamp('updated_at');       // TIMESTAMP
$table->timestamps();                  // created_at & updated_at
```

### Special Columns

```php
$table->enum('status', ['active', 'inactive']);  // ENUM
$table->json('metadata');              // JSON
$table->foreignId('user_id');          // BIGINT UNSIGNED (for foreign keys)
```

### Column Modifiers

```php
$table->string('name')->nullable();           // Allow NULL
$table->string('status')->default('active');  // Default value
$table->integer('sort_order')->unsigned();    // UNSIGNED
$table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
$table->timestamp('updated_at')->onUpdate('CURRENT_TIMESTAMP');
```

## Indexes

### Basic Indexes

```php
$table->index('email');                        // Single column index
$table->index(['name', 'email']);             // Composite index
$table->index(['name', 'email'], 'custom_name'); // Custom index name
```

### Unique Indexes

```php
$table->unique('email');                       // Unique constraint
$table->unique(['name', 'email']);            // Composite unique
```

### Foreign Keys

```php
$table->foreignId('user_id')
      ->references('id')
      ->on('users')
      ->onDelete('CASCADE');

// Or using the foreign method
$table->foreign('user_id')
      ->references('id')
      ->on('users')
      ->cascadeOnDelete();
```

## Modifying Existing Tables

### Adding Columns

```php
$this->schema->table('users', function (Blueprint $table) {
    $table->string('phone')->nullable();
    $table->date('birth_date')->nullable();
    $table->index('phone');
});
```

### Dropping Columns

```php
$this->schema->table('users', function (Blueprint $table) {
    $table->dropColumn('phone');
    $table->dropColumn('birth_date');
});
```

### Dropping Indexes

```php
$this->schema->table('users', function (Blueprint $table) {
    $table->dropIndex('users_phone_index');
});
```

## Migration Examples

### 1. Create Users Table

```php
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['first_name', 'last_name']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        $this->schema->drop('users');
    }
}
```

### 2. Create Posts with Foreign Key

```php
class CreatePostsTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title');
            $table->text('content');
            $table->enum('status', ['draft', 'published', 'archived'])
                  ->default('draft');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('CASCADE');

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $this->schema->drop('posts');
    }
}
```

### 3. Add Columns to Existing Table

```php
class AddProfileFieldsToUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();

            $table->index('phone');
        });
    }

    public function down(): void
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->dropIndex('users_phone_index');
            $table->dropColumn(['phone', 'birth_date', 'bio', 'avatar_url']);
        });
    }
}
```

### 4. Modify Column (Using Raw SQL)

```php
class ModifyUsersEmailColumn extends Migration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE users MODIFY COLUMN email VARCHAR(320) NOT NULL");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NOT NULL");
    }
}
```

## Command Line Interface

### Available Commands

| Command              | Description            | Example                                 |
| -------------------- | ---------------------- | --------------------------------------- |
| `status`             | Show migration status  | `php migrate.php status`                |
| `migrate`            | Run pending migrations | `php migrate.php migrate`               |
| `rollback`           | Rollback last batch    | `php migrate.php rollback`              |
| `rollback --steps=N` | Rollback N batches     | `php migrate.php rollback --steps=3`    |
| `create <name>`      | Create new migration   | `php migrate.php create add_user_roles` |
| `help`               | Show help information  | `php migrate.php help`                  |

### Migration Status Output

```
Migration Status:

Migration                                          Status     Batch Executed At
------------------------------------------------------------------------------------------
2025_01_01_120000_create_users_table              Executed   1     2025-01-01 12:00:00
2025_01_01_130000_create_posts_table              Executed   1     2025-01-01 12:00:00
2025_01_02_100000_add_profile_fields_to_users     Pending    -     -
```

## Best Practices

### 1. Naming Conventions

- Use descriptive names: `create_users_table`, `add_email_to_users`, `remove_unused_columns`
- Use snake_case for migration names
- Include the action: `create_`, `add_`, `remove_`, `modify_`

### 2. Migration Structure

- Keep migrations small and focused
- One logical change per migration
- Always provide rollback logic in `down()` method
- Test both `up()` and `down()` methods

### 3. Column Definitions

- Always specify column lengths for strings
- Use appropriate data types
- Add indexes for frequently queried columns
- Use foreign key constraints for referential integrity

### 4. Rollback Safety

- Ensure `down()` method properly reverses `up()` changes
- Test rollbacks in development environment
- Be careful with data-destructive operations

### 5. Production Deployments

- Always backup database before running migrations
- Test migrations in staging environment first
- Run migrations during maintenance windows for large changes
- Monitor migration execution time

## Advanced Features

### Raw SQL Execution

```php
public function up(): void
{
    $this->execute("CREATE INDEX CONCURRENTLY idx_users_email ON users(email)");
}
```

### Conditional Logic

```php
public function up(): void
{
    if (!$this->tableExists('users')) {
        $this->schema->create('users', function (Blueprint $table) {
            // Table definition
        });
    }

    if (!$this->columnExists('users', 'phone')) {
        $this->schema->table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
        });
    }
}
```

### Data Migrations

```php
public function up(): void
{
    // Schema change
    $this->schema->table('users', function (Blueprint $table) {
        $table->string('full_name')->nullable();
    });

    // Data migration
    $this->execute("
        UPDATE users
        SET full_name = CONCAT(first_name, ' ', last_name)
        WHERE full_name IS NULL
    ");
}
```

## Integration with Application

### Using in Service Providers

```php
class DatabaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Run migrations automatically in development
        if ($_ENV['APP_ENV'] === 'development') {
            $migrationManager = $this->container->resolve('MigrationManager');
            $migrationManager->migrate();
        }
    }
}
```

### Programmatic Usage

```php
// Get migration manager from container
$migrationManager = Application::resolve('MigrationManager');

// Check status
$status = $migrationManager->status();

// Run migrations
$result = $migrationManager->migrate();

// Rollback
$result = $migrationManager->rollback(2);
```

## Troubleshooting

### Common Issues

**Migration fails with "Class not found"**

- Ensure migration file follows naming convention
- Check that class name matches filename (StudlyCase)
- Verify `require_once` path is correct

**Foreign key constraint fails**

- Ensure referenced table exists
- Check that referenced column exists and has correct type
- Verify data integrity before adding constraint

**Migration hangs or times out**

- Large table modifications may take time
- Consider breaking into smaller migrations
- Use `ALGORITHM=INPLACE` for MySQL when possible

**Rollback fails**

- Ensure `down()` method properly reverses `up()` changes
- Check for data dependencies
- May need to handle data cleanup manually

### Recovery Strategies

**If migration fails mid-execution:**

1. Check database state manually
2. Fix any partial changes
3. Update migrations table if needed
4. Re-run migration

**If rollback is not possible:**

1. Create new migration to fix issues
2. Update migrations table manually if needed
3. Document the resolution

The migration system provides a robust foundation for managing database schema changes while maintaining data integrity and deployment safety.
