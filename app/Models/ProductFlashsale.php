<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::saving(function (self $productFlashsale): void {
            if ((float) $productFlashsale->discount_percentage < 25) {
                throw ValidationException::withMessages([
                    'discount_percentage' => ['Diskon flashsale minimal 25%.'],
                ]);
            }
        });
    }

    public function flashsale(): BelongsTo
    {
        return $this->belongsTo(Flashsale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(FlashsaleReservation::class);
    }
}
