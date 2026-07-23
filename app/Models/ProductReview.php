<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\ProductStatsService;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductReview extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'product_id', 'transaction_product_id', 'customer_id', 'rating', 'review',
        'reviewer_name', 'is_anonymous', 'is_admin',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_admin' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $review) => ProductStatsService::bustProduct($review->product_id));
        static::deleted(fn (self $review) => ProductStatsService::bustProduct($review->product_id));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transactionProduct(): BelongsTo
    {
        return $this->belongsTo(TransactionProduct::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Contain, 300, 300)->nonQueued();
    }
}
