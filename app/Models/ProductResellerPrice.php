<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductResellerPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id', 'product_id', 'price',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function wholesales(): HasMany
    {
        return $this->hasMany(ProductWholesale::class, 'product_id', 'product_id')
            ->where('reseller_id', $this->reseller_id);
    }
}
