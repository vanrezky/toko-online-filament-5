<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFlashsale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'flashsale_id',
        'discount_percentage',
        'stock',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function flashsale(): BelongsTo
    {
        return $this->belongsTo(Flashsale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
