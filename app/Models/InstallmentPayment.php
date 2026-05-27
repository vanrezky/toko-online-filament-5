<?php

namespace App\Models;

use App\Enums\InstallmentPaymentStatus;
use App\Enums\InstallmentStatus;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'installment_id',
        'installment_number',
        'amount',
        'due_date',
        'billing_month',
        'paid_amount',
        'paid_date',
        'payment_method',
        'collection_method',
        'payroll_status',
        'payroll_batch_reference',
        'submitted_at',
        'confirmed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'billing_month' => 'date',
        'paid_date' => 'date',
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InstallmentPayment $payment) {
            if (empty($payment->code)) {
                $payment->code = CodeGeneratorService::generateUnique($payment, 'code', 'PAY');
            }

            if (empty($payment->billing_month) && ! empty($payment->due_date)) {
                $payment->billing_month = $payment->due_date->copy()->startOfMonth();
            }

            if (empty($payment->collection_method) && ! empty($payment->payment_method)) {
                $payment->collection_method = $payment->payment_method;
            }
        });
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', InstallmentPaymentStatus::unpaid->value);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InstallmentPaymentStatus::overdue->value)
            ->orWhere(function ($q) {
                $q->where('status', InstallmentPaymentStatus::unpaid->value)
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
        $newPaidAmount = min((float) $this->amount, (float) $this->paid_amount + $paidAmount);
        $status = $newPaidAmount >= (float) $this->amount
            ? InstallmentPaymentStatus::paid->value
            : InstallmentPaymentStatus::partial->value;
        $wasPaid = $this->status === InstallmentPaymentStatus::paid->value;

        $this->update([
            'paid_amount' => $newPaidAmount,
            'paid_date' => now(),
            'payment_method' => $method,
            'collection_method' => $method,
            'status' => $status,
            'payroll_status' => $status === 'paid' ? 'confirmed_paid' : $this->payroll_status,
            'confirmed_at' => $status === 'paid' ? now() : $this->confirmed_at,
            'notes' => $notes,
        ]);

        $installment = $this->installment;
        $deltaPaidAmount = max(0, $newPaidAmount - (float) $this->getOriginal('paid_amount'));
        $installment->increment('paid_amount', $deltaPaidAmount);

        if (! $wasPaid && $status === 'paid') {
            $installment->increment('paid_installments');
        }

        $paidInstallments = $installment->payments()->where('status', InstallmentPaymentStatus::paid->value)->count();

        if ($paidInstallments >= $installment->tenor) {
            $installment->update(['status' => InstallmentStatus::Completed->value]);
        }
    }
}
