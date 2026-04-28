---
name: resource-api
description: Create API Resources (transformers) for formatting Eloquent models into JSON responses. Follow the project's patterns for data transformation, conditional loading, and nested resources.
license: MIT
compatibility: opencode
metadata:
  category: backend
  framework: laravel
---

## What I do

I help create API Resources for transforming Eloquent models into structured JSON responses.

## Resource Structure

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,                          // Use UUID as public ID
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            
            // Relationships (conditional loading)
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('productVariants')),
            
            // Media (Spatie)
            'thumbnail' => $this->getFirstMediaUrl('default', 'thumb'),
            'images' => $this->getMedia()->map(fn($m) => [
                'url' => $m->getUrl(),
                'thumb' => $m->getUrl('thumb'),
            ]),
            
            // Computed fields
            'price_formatted' => 'Rp ' . number_format($this->price, 0, ',', '.'),
            'is_on_sale' => !empty($this->sale_price) && $this->sale_price < $this->price,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## Simple Resource

```php
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->getFirstMediaUrl() ?: null,
        ];
    }
}
```

## Resource with Conditional Loading

```php
class FlashsaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            
            // Only include when loaded
            'products' => FlashsaleProductResource::collection(
                $this->whenLoaded('products')
            ),
            
            // Conditional field
            'is_active' => $this->when(
                $request->user()?->isAdmin(),
                $this->is_active
            ),
        ];
    }
}
```

## Resource with Custom Logic

```php
class ProductSimpleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get authenticated customer for reseller pricing
        $customer = auth('customer')->user();
        $resellerId = $customer?->reseller_id;

        // Determine price based on customer type
        $targetPrice = $this->price;
        if ($resellerId) {
            $resellerPrice = $this->resellerPrices
                ->where('reseller_id', $resellerId)
                ->first();
            if ($resellerPrice) {
                $targetPrice = $resellerPrice->price;
            }
        }

        // Calculate discount percentage
        $discountPercentage = null;
        if ($this->sale_price && $targetPrice > 0) {
            $discountPercentage = round(
                (($this->sale_price - $targetPrice) / $this->sale_price) * 100
            );
        }

        return [
            'id' => $this->uuid,
            'name' => Str::limit($this->name, 35, ''),
            'slug' => $this->slug,
            'category_name' => $this->category?->name,
            'price' => $targetPrice,
            'sale_price' => $this->sale_price,
            'discount_percentage' => $discountPercentage,
            'thumbnail' => $this->resource->getMedia()->first()?->getUrl('thumb'),
            'currency' => currency(), // Helper function
        ];
    }
}
```

## Resource Collection

```php
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'order_number' => strtoupper(substr($this->uuid, 0, 8)),
            'status' => $this->status,
            'status_label' => ucfirst($this->status),
            'total_amount' => $this->total_amount,
            'total_formatted' => 'Rp ' . number_format($this->total_amount, 0, ',', '.'),
            
            'customer' => [
                'name' => $this->customer?->full_name,
                'email' => $this->customer?->email,
            ],
            
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            
            'created_at' => $this->created_at->format('d M Y, H:i'),
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## Common Patterns

### UUID as Public ID
```php
'id' => $this->uuid,  // Never expose auto-increment ID
```

### Formatted Currency
```php
'price_formatted' => 'Rp ' . number_format($this->price, 0, ',', '.'),
// or use helper:
'price_formatted' => toMoney($this->price),
```

### Media URLs (Spatie)
```php
'image' => $this->getFirstMediaUrl() ?: null,
'image_thumb' => $this->getFirstMediaUrl('thumb') ?: null,
'images' => $this->getMedia()->map(fn($m) => [
    'url' => $m->getUrl(),
    'thumb' => $m->getUrl('thumb'),
    'name' => $m->name,
]),
```

### Conditional Relationships
```php
// Only include if loaded (prevents N+1)
'category' => new CategoryResource($this->whenLoaded('category')),

// Collection with whenLoaded
'products' => ProductResource::collection($this->whenLoaded('products')),

// Null-safe nested access
'category_name' => $this->category?->name,
```

### Enum Values
```php
'type' => $this->voucher_type->value,           // Raw value
'type_label' => $this->voucher_type->getLabel(), // Human readable
```

### Date Formatting
```php
'created_at' => $this->created_at,                    // Full ISO
'created_at_formatted' => $this->created_at->format('d M Y, H:i'),
'created_at_human' => $this->created_at->diffForHumans(),
```

## When to use me

- Creating new API resources
- Formatting model data for JSON responses
- Transforming Eloquent relationships
- Adding computed fields to API output
- Creating nested resource structures

## Important Notes

- **Always use UUID** (`$this->uuid`) as public ID, never expose `$this->id`
- Use `$this->whenLoaded()` for relationships to prevent N+1 queries
- Use `$this->when()` for conditional fields
- Use `?->` (nullsafe operator) for optional relationships
- Format currency using `number_format()` or `toMoney()` helper
- Format dates using `->format()` or `->diffForHumans()`
- For media, use Spatie's `getFirstMediaUrl()` or `getMedia()`
- Keep resources focused — create separate resources for list vs detail views
- Use `Str::limit()` for long text fields in list resources
- Resource naming: `{Model}Resource` for detail, `{Model}SimpleResource` for list
