<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionBillingStatus;
use App\Enums\EmailTemplateCode;
use App\Services\CodeGeneratorService;
use App\Services\EmailTemplateService;
use App\Settings\GeneralSettings;
use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'receipt_code',
        'delivery_date',
        'status',
        'notes',
        'uuid',
        'code',
        'timelimit',
        'complete_date',
        'request_cancellation',
    ];

    protected $casts = [
        'timelimit' => 'datetime',
        'billing_due_date' => 'date',
        'billing_status' => TransactionBillingStatus::class,
        'delivery_date' => 'datetime',
        'complete_date' => 'datetime',
        'status' => TransactionStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->code)) {
                $transaction->code = CodeGeneratorService::generateUnique($transaction, 'code', 'TRX');
            }
        });

        static::updated(function (Transaction $transaction) {
            if (! $transaction->wasChanged('status')) {
                return;
            }

            $transaction->loadMissing('customer', 'shippingDetails');
            $customer = $transaction->customer;
            if (! $customer?->email) {
                return;
            }

            $oldStatusRaw = $transaction->getOriginal('status');
            $newStatusRaw = $transaction->status;

            $oldStatusEnum = $oldStatusRaw instanceof TransactionStatus
                ? $oldStatusRaw
                : TransactionStatus::tryFrom((string) $oldStatusRaw);

            $newStatusEnum = $newStatusRaw instanceof TransactionStatus
                ? $newStatusRaw
                : TransactionStatus::tryFrom((string) $newStatusRaw);

            if (($newStatusEnum?->value ?? (string) $newStatusRaw) === TransactionStatus::packed->value) {
                return;
            }

            $firstShipping = $transaction->shippingDetails->first();

            $generalSettings = app(GeneralSettings::class);
            $websiteName = $generalSettings?->site_name ?? config('app.name');

            app(EmailTemplateService::class)->send(
                code: EmailTemplateCode::ORDER_STATUS_CHANGED->value,
                email: $customer->email,
                placeholders: [
                    'customer_name' => $customer->full_name ?? trim((string) $customer->first_name . ' ' . (string) $customer->last_name),
                    'order_id' => $transaction->code ?? $transaction->uuid,
                    'old_status' => (string) ($oldStatusEnum?->getLabel() ?? $oldStatusRaw ?? '-'),
                    'new_status' => (string) ($newStatusEnum?->getLabel() ?? $newStatusRaw ?? '-'),
                    'tracking_number' => (string) ($transaction->receipt_code ?? ''),
                    'courier_name' => (string) ($firstShipping?->courier_name ?? ''),
                    'website_name' => (string) $websiteName,
                ],
                queue: true,
                queuePriority: 'default',
            );
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

    public function returns(): HasMany
    {
        return $this->hasMany(TransactionReturn::class);
    }

    public function installment(): HasOne
    {
        return $this->hasOne(Installment::class);
    }

    public function emailLogs(): MorphMany
    {
        return $this->morphMany(EmailLog::class, 'reference');
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
