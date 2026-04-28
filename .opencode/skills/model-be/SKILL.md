---
name: model-be
description: Create Eloquent models following the project's patterns. Use this for creating models with UUIDs, traits, relationships, scopes, casts, and accessors.
license: MIT
compatibility: opencode
metadata:
  category: backend
  framework: laravel
---

## What I do

I help create Eloquent models following this project's established patterns.

## Model Structure

```php
<?php

namespace App\Models;

use App\Traits\HasMeta;
use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, HasUuidTrait, HasMeta, InteractsWithMedia;

    protected $fillable = [
        'name', 'slug', 'category_id', 'description', 
        'price', 'stock', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'digital' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Use slug for route model binding
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // UUID key name (if not 'uuid')
    protected string $uuidKeyName = 'uuid';
}
```

## Available Traits

| Trait | Location | Purpose |
|-------|----------|---------|
| `HasUuidTrait` | `App\Traits\HasUuidTrait` | Auto-generate UUID on creating |
| `HasMeta` | `App\Traits\HasMeta` | Add morphOne Meta relation |
| `HasProfilePictureTrait` | `App\Traits\HasProfilePictureTrait` | Profile picture helpers |
| `InteractsWithMedia` | `Spatie\MediaLibrary` | Media/file uploads |
| `HasFactory` | `Illuminate\Database\Eloquent` | Model factories |

## Relationships Patterns

```php
// BelongsTo
public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}

// BelongsTo with custom foreign key
public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}

// HasMany
public function variants(): HasMany
{
    return $this->hasMany(ProductVariant::class);
}

// HasMany with ordering
public function orderedItems(): HasMany
{
    return $this->hasMany(OrderItem::class)->orderBy('created_at', 'desc');
}

// MorphOne (for Meta)
public function meta(): MorphOne
{
    return $this->morphOne(Meta::class, 'metagable');
}
```

## Scopes Patterns

```php
// Active scope (most common)
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

// Featured scope
public function scopeFeatured($query)
{
    return $query->where('is_featured', true);
}

// Category scope
public function scopeByCategory($query, int $categoryId)
{
    return $query->where('category_id', $categoryId);
}

// Search scope
public function scopeSearch($query, string $search)
{
    return $query->where('name', 'like', "%{$search}%");
}
```

## Accessors & Mutators (Attribute Casting)

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

// Accessor only
protected function priceFormatted(): Attribute
{
    return Attribute::make(
        get: fn(mixed $value, array $attributes) => 'Rp ' . number_format($attributes['price'], 0, ',', '.'),
    );
}

// Accessor with calculation
protected function discountPercentage(): Attribute
{
    return Attribute::make(
        get: function (mixed $value, array $attributes) {
            if (empty($attributes['sale_price']) || $attributes['sale_price'] == 0) {
                return 0;
            }
            return round((($attributes['sale_price'] - $attributes['price']) / $attributes['sale_price']) * 100);
        },
    );
}

// Mutator (set)
protected function name(): Attribute
{
    return Attribute::make(
        set: fn(string $value) => ucfirst($value),
    );
}

// Both get and set
protected function slug(): Attribute
{
    return Attribute::make(
        get: fn(string $value) => $value,
        set: fn(string $value) => \Illuminate\Support\Str::slug($value),
    );
}
```

## Media Library (Spatie)

```php
use Spatie\Image\Enums\Fit;

public function registerAllMediaConversions(?Media $media = null): void
{
    $this
        ->addMediaConversion('thumb')
        ->fit(Fit::Contain, 300, 300)
        ->nonQueued();

    $this
        ->addMediaConversion('medium')
        ->fit(Fit::Contain, 600, 600)
        ->nonQueued();
}
```

## Common Casts

```php
protected $casts = [
    'is_active' => 'boolean',
    'is_featured' => 'boolean',
    'price' => 'decimal:2',
    'stock' => 'integer',
    'metadata' => 'json',
    'published_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

## Business Logic Methods

```php
public function calculatePrice(int $quantity, ?ProductVariant $variant = null): array
{
    $price = $variant ? $variant->price : ($this->sale_price ?: $this->price);
    
    return [
        'price' => $price,
        'discount' => $this->sale_price ? ($this->price - $this->sale_price) : 0,
    ];
}

public function isInStock(): bool
{
    return $this->stock > $this->security_stock;
}

public function decrementStock(int $quantity): void
{
    $this->decrement('stock', $quantity);
}
```

## When to use me

- Creating new Eloquent models
- Adding relationships to models
- Implementing scopes
- Adding accessors/mutators
- Setting up media conversions
- Adding business logic methods

## Important Notes

- **Always use `$fillable`** (not `$guarded`) for security
- Use `HasUuidTrait` for models that need UUIDs (most models in this project)
- Use `HasMeta` for models that need SEO meta data
- Use `InteractsWithMedia` for models with file uploads
- Define `$casts` for proper type casting
- Use `Attribute::make()` for accessors/mutators (Laravel 9+)
- Add `scopeActive()` for soft-delete-like active/inactive pattern
- Use `getRouteKeyName()` if using slug instead of id for routes
- Business logic belongs in model methods or Service classes
- Check existing sibling models before creating new ones
