<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPaymentResponse extends Model
{
    protected $fillable = [
        'transaction_id',
        'provider',
        'source',
        'payment_channel',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
