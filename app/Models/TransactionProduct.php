<?php

namespace App\Models;

use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionProduct extends Model
{
    use HasFactory, HasUuidTrait;

    protected $table = 'transcation_products';

    protected $fillable = [
        'uuid',
        'transaction_id',
        'customer_id',
        'is_digital',
        'product_id',
        'product_name',
        'product_code',
        'variant_name',
        'variant_sku',
        'weight_snapshot',
        'warehouse_id',
        'quantity',
        'price',
        'discount',
        'line_subtotal',
        'description',
        'product_snapshot',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'line_subtotal' => 'decimal:2',
    ];

    protected $appends = [
        'display_product_name',
        'display_variant_name',
    ];
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getSubtotalAttribute(): float
    {
        if ($this->line_subtotal !== null) {
            return (float) $this->line_subtotal;
        }

        return (($this->price - $this->discount) * $this->quantity);
    }

    public function getDisplayProductNameAttribute(): string
    {
        return (string) (
            $this->product_name
            ?? $this->product_snapshot['product_name']
            ?? 'Item transaksi historis #' . $this->id
        );
    }

    public function getDisplayVariantNameAttribute(): ?string
    {
        $variantName = $this->variant_name
            ?? $this->product_snapshot['variant_name']
            ?? $this->description;

        return filled($variantName) ? (string) $variantName : null;
    }
}
