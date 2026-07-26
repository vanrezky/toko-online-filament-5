<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashsaleReservation extends Model
{
    use HasFactory;

    protected $fillable = ['transaction_id', 'product_flashsale_id', 'quantity', 'released_at'];

    protected $casts = ['quantity' => 'integer', 'released_at' => 'datetime'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function productFlashsale(): BelongsTo
    {
        return $this->belongsTo(ProductFlashsale::class);
    }
}
