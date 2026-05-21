<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_return_id',
        'transaction_product_id',
        'qty',
        'amount',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transactionReturn(): BelongsTo
    {
        return $this->belongsTo(TransactionReturn::class);
    }

    public function transactionProduct(): BelongsTo
    {
        return $this->belongsTo(TransactionProduct::class, 'transaction_product_id');
    }
}
