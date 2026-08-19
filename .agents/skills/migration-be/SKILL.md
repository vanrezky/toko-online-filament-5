---
name: migration-be
description: Create Laravel database migrations following the project's patterns. Use this for creating tables, indexes, foreign keys, and columns.
license: MIT
compatibility: opencode
metadata:
  category: backend
  framework: laravel
---

## What I do

I help create Laravel database migrations following this project's established patterns.

## Migration Structure

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid()->index();
            $table->string('name')->index();
            $table->string('slug');
            $table->foreignId('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

## Common Column Types

```php
// IDs and Keys
$table->id();                                    // Big unsigned auto-increment PK
$table->uuid()->index();                         // UUID string with index
$table->foreignId('user_id');                    // Unsigned big integer FK
$table->unsignedBigInteger('parent_id')->nullable();

// Strings
$table->string('name')->index();                 // VARCHAR(255) with index
$table->string('code', 50)->unique();            // VARCHAR(50) with unique
$table->text('description');                     // TEXT
$table->longText('content');                     // LONGTEXT
$table->tinyText('summary');                     // TINYTEXT

// Numbers
$table->integer('quantity')->default(0);         // INTEGER
$table->unsignedInteger('stock')->default(0);    // Unsigned INTEGER
$table->bigInteger('amount');                    // BIGINT
$table->unsignedBigInteger('views')->default(0);
$table->double('price', 15, 2);                  // DECIMAL(15,2)
$table->float('rating', 2, 1);                   // FLOAT(2,1)
$table->decimal('discount', 10, 2)->nullable();

// Boolean
$table->boolean('is_active')->default(true)->index();
$table->boolean('is_featured')->default(false);
$table->boolean('digital')->default(false);

// Dates & Times
$table->timestamps();                            // created_at + updated_at
$table->timestamp('published_at')->nullable();
$table->date('birth_date')->nullable();
$table->dateTime('expired_at')->nullable();
$table->softDeletes();                           // deleted_at

// JSON
$table->json('metadata')->nullable();
$table->json('settings')->nullable();

// Enums
$table->enum('status', ['active', 'inactive', 'pending'])->default('pending');

// Other
$table->text('digital_url')->nullable();
$table->string('variant')->nullable();
```

## Foreign Key Patterns

```php
// Inline foreign key (shorthand)
$table->foreignId('category_id')
    ->references('id')
    ->on('categories')
    ->onDelete('cascade');

// Separate foreign key definition
$table->unsignedBigInteger('warehouse_id')->nullable();
$table->foreign('warehouse_id')
    ->references('id')
    ->on('warehouses')
    ->onDelete('cascade');

// Nullable foreign key
$table->foreignId('parent_id')
    ->nullable()
    ->references('id')
    ->on('categories')
    ->onDelete('set null');
```

## Index Patterns

```php
// Single column index
$table->index('is_active');
$table->index('slug');
$table->index('created_at');

// Unique index
$table->unique('code');
$table->unique(['email', 'deleted_at']);

// Composite index
$table->index(['category_id', 'is_active']);
$table->index(['user_id', 'created_at']);

// Full text index (MySQL)
$table->fullText('description');
```

## Common Migration Patterns

### Creating a Simple Table
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->uuid()->index();
    $table->string('name')->index();
    $table->string('slug')->unique();
    $table->string('icon')->nullable();
    $table->boolean('is_active')->default(true)->index();
    $table->boolean('is_featured')->default(false);
    $table->integer('sort_order')->default(0);
    $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->timestamps();
});
```

### Pivot Table (Many-to-Many)
```php
Schema::create('product_tag', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->references('id')->on('products')->onDelete('cascade');
    $table->foreignId('tag_id')->references('id')->on('tags')->onDelete('cascade');
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->unique(['product_id', 'tag_id']);
});
```

### Adding Columns to Existing Table
```php
Schema::table('products', function (Blueprint $table) {
    $table->string('sku')->nullable()->after('code');
    $table->unsignedInteger('view_count')->default(0)->after('stock');
    $table->index('sku');
});
```

### Modifying Columns
```php
Schema::table('products', function (Blueprint $table) {
    $table->string('name', 500)->change(); // Increase length
    $table->boolean('is_active')->default(false)->change(); // Change default
});
```

### Dropping Columns
```php
Schema::table('products', function (Blueprint $table) {
    $table->dropColumn('old_field');
    $table->dropColumn(['field1', 'field2', 'field3']);
});
```

### Dropping Foreign Keys
```php
Schema::table('products', function (Blueprint $table) {
    $table->dropForeign(['category_id']);
    $table->dropColumn('category_id');
});
```

## Migration Naming Conventions

| Action | Convention | Example |
|--------|-----------|---------|
| Create table | `create_{table}_table` | `create_products_table` |
| Add columns | `add_{columns}_to_{table}_table` | `add_sku_to_products_table` |
| Modify columns | `modify_{columns}_in_{table}_table` | `modify_price_in_products_table` |
| Drop columns | `drop_{columns}_from_{table}_table` | `drop_old_field_from_products_table` |
| Create pivot | `create_{table1}_{table2}_table` | `create_product_tag_table` |

## When to use me

- Creating new tables
- Adding/modifying columns
- Adding indexes or foreign keys
- Creating pivot tables
- Renaming tables or columns
- Dropping tables or columns

## Important Notes

- **Always add `$table->uuid()->index()`** for models using `HasUuidTrait`
- **Always add `$table->timestamps()`** unless specifically not needed
- Use `->index()` on columns frequently used in WHERE clauses
- Use `->nullable()` for optional fields
- Use `->default()` for columns with default values
- Use `->after('column')` to position new columns logically
- Add foreign keys with `->onDelete('cascade')` or `->onDelete('set null')`
- Check existing migrations in `database/migrations/` for naming conventions
- Use `Schema::hasTable('table_name')` to check if table exists before creating
- For nullable booleans, use `->default(false)` not `->default(null)`
- Use `$table->softDeletes()` for tables that need soft delete capability
