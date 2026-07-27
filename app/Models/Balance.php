<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    use HasFactory;

    public const TYPE_TOP_UP = 'top_up';
    public const TYPE_ADJUSTMENT_DEBIT = 'adjustment_debit';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_REFUND = 'refund';

    protected $fillable = [
        'customer_id', 'performed_by_id', 'transaction_id', 'amount', 'charge', 'balance_before',
        'post_balance', 'trx_type', 'type', 'notes', 'remark',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'post_balance' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function updateBalance(int $customer_id, float|int $amount, float|int $charge, float|int $post_balance, string $trx_type = '+', ?string $notes = null, ?string $remark = null)
    {
        $defaultNotes = $trx_type === '+' ? 'Add Balance' : 'Reduce Balance';
        $defaultRemark = $trx_type === '+' ? 'Add Balance' : 'Reduce Balance';

        return [
            'customer_id' => $customer_id,
            'amount' => $amount,
            'charge' => $charge,
            'post_balance' => $post_balance,
            'trx_type' => $trx_type,
            'notes' => $notes ?: $defaultNotes,
            'remark' => $remark ?: $defaultRemark
        ];
    }
}
