<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory, HasUuidTrait;

    protected $fillable = [
        'customer_id',
        'customer_address_id',
        'weight',
        'shipping_cost',
        'cod',
        'cod_fee',
        'payment_method',
        'payment_type',
        'billing_due_date',
        'billing_status',
        'installment_plan_id',
        'status',
        'notes',
        'uuid',
        'code',
        'timelimit',
    ];

    protected $casts = [
        'timelimit' => 'datetime',
        'billing_due_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->code)) {
                $transaction->code = CodeGeneratorService::generateUnique($transaction, 'code', 'TRX');
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(TransactionProduct::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function shippingDetails(): HasMany
    {
        return $this->hasMany(TransactionShippingDetail::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(TransactionVoucher::class);
    }

    public function installment(): HasOne
    {
        return $this->hasOne(Installment::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->products->sum(fn($p) => $p->price * $p->quantity);
    }

    public function getProductDiscountAttribute(): float
    {
        return (float) $this->products->sum('discount');
    }

    public function getVoucherDiscountAttribute(): float
    {
        return (float) $this->vouchers->sum('discount_amount');
    }

    public function getTotalDiscountAttribute(): float
    {
        return $this->product_discount + $this->voucher_discount;
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->subtotal - $this->total_discount + $this->shipping_cost + ($this->cod ? $this->cod_fee : 0);
    }

    public function getTotalItemsAttribute(): int
    {
        return (int) $this->products->sum('quantity');
    }

    public function getDigitalProductsCountAttribute(): int
    {
        return (int) $this->products->where('is_digital', true)->count();
    }

    public function hasDigitalProducts(): bool
    {
        return $this->digital_products_count > 0;
    }
}
