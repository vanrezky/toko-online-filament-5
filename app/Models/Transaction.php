<?php

namespace App\Models;

use App\Enums\EmailTemplateCode;
use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Modules\Platform\Audit\Concerns\HasPlatformAuditMetadata;
use App\Services\CodeGeneratorService;
use App\Services\EmailTemplateService;
use App\Services\ProductStatsService;
use App\Settings\GeneralSettings;
use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    use HasFactory, HasPlatformAuditMetadata, HasUuidTrait, LogsActivity;

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
        'customer_timezone',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('platform')
            ->logOnly([
                'payment_type', 'payment_method', 'billing_status', 'status', 'receipt_code',
                'delivery_date', 'complete_date', 'request_cancellation', 'shipping_cost', 'cod', 'cod_fee',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event): string => "transaction {$event}");
    }

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

            ProductStatsService::bustProducts($transaction->products()->pluck('product_id'));

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
                    'customer_name' => $customer->full_name ?? trim((string) $customer->first_name.' '.(string) $customer->last_name),
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

    public function recordPaymentGatewayResponse(string $source, ?string $paymentChannel, array $response): void
    {
        $paymentResponse = $this->paymentResponses()->firstOrNew([
            'provider' => $this->payment_method,
            'source' => $source,
        ]);

        if (filled($paymentChannel)) {
            $paymentResponse->payment_channel = $paymentChannel;
        }

        $paymentResponse->response = $response;
        $paymentResponse->save();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentResponses(): HasMany
    {
        return $this->hasMany(TransactionPaymentResponse::class);
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

    public function flashsaleReservations(): HasMany
    {
        return $this->hasMany(FlashsaleReservation::class);
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
        return (float) $this->products->sum(function ($product) {
            $baseUnitPrice = (float) $product->price;
            $discountPerUnit = (float) $product->discount;
            $finalLineSubtotal = (float) $product->subtotal;
            $baseLineSubtotal = $baseUnitPrice * (int) $product->quantity;

            // Older rows stored `price` as final unit price while newer rows store normal unit price.
            if ($discountPerUnit > 0 && abs($finalLineSubtotal - $baseLineSubtotal) < 0.01) {
                $baseUnitPrice += $discountPerUnit;
            }

            return $baseUnitPrice * (int) $product->quantity;
        });
    }

    public function getProductDiscountAttribute(): float
    {
        return (float) $this->products->sum(function ($product) {
            $baseUnitPrice = (float) $product->price;
            $discountPerUnit = (float) $product->discount;
            $finalLineSubtotal = (float) $product->subtotal;
            $baseLineSubtotal = $baseUnitPrice * (int) $product->quantity;

            if ($discountPerUnit > 0 && abs($finalLineSubtotal - $baseLineSubtotal) < 0.01) {
                $baseUnitPrice += $discountPerUnit;
                $baseLineSubtotal = $baseUnitPrice * (int) $product->quantity;
            }

            return max(0, $baseLineSubtotal - $finalLineSubtotal);
        });
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
