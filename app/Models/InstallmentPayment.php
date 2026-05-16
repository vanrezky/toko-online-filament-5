<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'installment_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_amount',
        'paid_date',
        'payment_method',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'unpaid')
                  ->where('due_date', '<', now());
            });
    }

    public function scopeDueThisMonth($query)
    {
        return $query->whereYear('due_date', now()->year)
            ->whereMonth('due_date', now()->month);
    }

    public function markAsPaid(float $paidAmount, string $method, ?string $notes = null): void
    {
        $this->update([
            'paid_amount' => $paidAmount,
            'paid_date' => now(),
            'payment_method' => $method,
            'status' => 'paid',
            'notes' => $notes,
        ]);

        $installment = $this->installment;
        $installment->increment('paid_amount', $paidAmount);
        $installment->increment('paid_installments');

        if ($installment->paid_installments >= $installment->tenor) {
            $installment->update(['status' => 'completed']);
        }
    }
}