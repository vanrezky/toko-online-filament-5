<?php

namespace App\Models;

use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionReturn extends Model
{
    use HasFactory, HasUuidTrait;

    protected $fillable = [
        'uuid',
        'transaction_id',
        'customer_id',
        'status',
        'reason',
        'notes',
        'requested_at',
        'approved_at',
        'received_at',
        'refunded_at',
        'closed_at',
        'created_by',
        'approved_by',
        'processed_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionReturnItem::class);
    }
}
