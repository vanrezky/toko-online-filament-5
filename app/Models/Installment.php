<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Services\CodeGeneratorService;
use App\Traits\HasModelTrait;
use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installment extends Model
{
    use HasFactory, HasModelTrait, HasUuidTrait, SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'transaction_id',
        'customer_id',
        'installment_plan_id',
        'principal_amount',
        'fee_amount',
        'total_amount',
        'monthly_amount',
        'tenor',
        'paid_amount',
        'paid_installments',
        'status',
        'start_date',
        'expected_end_date',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'start_date' => 'date',
        'expected_end_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Installment $installment) {
            if (empty($installment->code)) {
                $installment->code = CodeGeneratorService::generateUnique($installment, 'code', 'INS');
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', InstallmentStatus::Active->value);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InstallmentStatus::Overdue->value);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    public function getRemainingInstallmentsAttribute(): int
    {
        return $this->tenor - $this->paid_installments;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->payments()
            ->where('status', InstallmentStatus::Overdue->value)
            ->exists();
    }
}
