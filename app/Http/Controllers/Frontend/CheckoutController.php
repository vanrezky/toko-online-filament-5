<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\CartStatus;
use App\Enums\CourierCode;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Jobs\SendPaymentRequestNotification;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\InstallmentPlan;
use App\Models\Transaction;
use App\Models\Warehouse;
use App\Services\ApicoidOngkirService;
use App\Services\BillingCycleService;
use App\Services\CacheService;
use App\Services\CreditLimitService;
use App\Services\InstallmentService;
use App\Services\PaymentGatewayService;
use App\Services\VoucherCookieService;
use App\Services\VoucherService;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    protected VoucherCookieService $cookieService;

    protected VoucherService $voucherService;

    protected InstallmentService $installmentService;

    protected CreditLimitService $creditLimitService;

    public function __construct(
        VoucherCookieService $cookieService,
        VoucherService $voucherService,
        InstallmentService $installmentService,
        CreditLimitService $creditLimitService
    ) {
        $this->cookieService = $cookieService;
        $this->voucherService = $voucherService;
        $this->installmentService = $installmentService;
        $this->creditLimitService = $creditLimitService;
    }

    public function __invoke(Request $request, PaymentGatewayService $paymentGatewayService, GeneralSettings $generalSettings)
    {
        $customer = Auth::guard('customer')->user();

        $cart = Cart::with(['items.product.media', 'items.product.warehouse', 'items.productVariant.variantAttributes.productAttribute', 'items.productVariant.variantAttributes.productAttributeOption'])
            ->active()
            ->where('customer_id', $customer->id)
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('frontend.cart')->with('error', __('messages.error.cart_empty'));
        }

        $addresses = CustomerAddress::with(['province', 'district', 'subDistrict', 'village'])
            ->where('customer_id', $customer->id)
            ->orderBy('is_featured', 'desc')
            ->get();

        $pendingVouchers = $this->cookieService->get();
        $validatedVouchers = $this->voucherService->validateFromCookie($pendingVouchers, $cart, $customer);

        return Inertia::render('Checkout/Index', [
            'cart' => CartResource::make($cart),
            'addresses' => \App\Http\Resources\AddressResource::collection($addresses),
            'pendingVouchers' => $pendingVouchers,
            'validatedVouchers' => $validatedVouchers,
            'activeGateway' => $paymentGatewayService->getActiveGatewayAlias(),
            'installmentPlans' => \App\Models\InstallmentPlan::active()->get(),
            'creditLimit' => [
                'remaining' => $customer->remaining_credit_limit,
                'effective' => $customer->effective_credit_limit,
            ],
            'installmentMinOrderAmount' => (int) ($generalSettings->installment_min_order_amount ?? 1000000),
        ]);
    }

    public function getShippingCosts(Request $request, ApicoidOngkirService $ongkirService, \App\Settings\CourierSettings $courierSettings)
    {
        $request->validate([
            'address_id' => 'required|exists:customer_addresses,id',
        ]);

        $customer = Auth::guard('customer')->user();
        $cart = Cart::with(['items.product.warehouse', 'items.productVariant'])
            ->active()
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $address = CustomerAddress::with('village')->findOrFail($request->address_id);

        if (! $address->village) {
            return response()->json(['error' => __('messages.error.address_no_village')], 422);
        }

        $cartHash = md5($cart->items->sortBy('id')->map(function ($item) {
            return $item->product_id . '-' . ($item->product_variant_id ?? '0') . '-' . $item->quantity . '-' . $item->price;
        })->implode('|'));

        $shippingCostsVersion = (int) Cache::get('shipping_costs_version', 1);
        $cacheKey = "shipping_costs_v{$shippingCostsVersion}_{$customer->id}_{$address->id}_{$cartHash}";
        $shippingResults = CacheService::remember($cacheKey, 1800, function () use ($cart, $address, $ongkirService, $courierSettings) {
            $warehouseGroups = $cart->items->groupBy(function ($item) {
                return $item->product->warehouse_id ?: 0;
            });

            $activeCouriers = \App\Models\Courier::active()->get();
            $isPickupActive = $activeCouriers->contains('code', CourierCode::PICKUP->value);
            $isKurirTokoActive = $activeCouriers->contains('code', CourierCode::KURIR_TOKO->value);

            $pickupOption = $isPickupActive ? [
                'courier_code' => CourierCode::PICKUP->value,
                'courier_name' => __('labels.shipping.pick_up'),
                'price' => 0,
                'estimation' => __('labels.shipping.estimation_same_day'),
            ] : null;

            $kurirTokoOption = $isKurirTokoActive ? [
                'courier_code' => CourierCode::KURIR_TOKO->value,
                'courier_name' => __('labels.shipping.kurir_toko'),
                'price' => $courierSettings->kurir_toko_price,
                'estimation' => __('labels.shipping.estimation_1_2_days'),
            ] : null;

            $results = [];

            foreach ($warehouseGroups as $warehouseId => $items) {
                $warehouse = Warehouse::with('village')->find($warehouseId);
                if (! $warehouse || ! $warehouse->village) {
                    continue;
                }

                $totalWeight = $items->sum(fn($item) => ($item->productVariant?->weight ?: $item->product->weight) * $item->quantity);

                $costs = $ongkirService->getShippingCost(
                    $warehouse->village->apicoid_code,
                    $address->village->apicoid_code,
                    $totalWeight
                );

                $options = [];
                if (isset($costs['status']) && $costs['status'] === 'success') {
                    $options = $costs['result'];
                }

                if ($pickupOption) {
                    $options[] = $pickupOption;
                }

                if ($kurirTokoOption) {
                    $options[] = $kurirTokoOption;
                }

                if (! empty($options)) {
                    $results[] = [
                        'warehouse_id' => $warehouseId,
                        'warehouse_name' => $warehouse->name,
                        'weight' => $totalWeight,
                        'options' => $options,
                    ];
                }
            }


            return $results;
        });

        return response()->json($shippingResults);
    }

    public function store(Request $request, PaymentGatewayService $paymentGatewayService, GeneralSettings $generalSettings, BillingCycleService $billingCycleService)
    {
        $request->validate([
            'address_id' => 'nullable|exists:customer_addresses,id',
            'shipping_methods' => 'required|array',
            'payment_type' => 'required|in:full,installment',
            'installment_plan_id' => 'nullable|exists:installment_plans,id',
            'notes' => 'nullable|string',
        ]);

        $customer = Auth::guard('customer')->user();
        $cart = Cart::with(['items.product.warehouse', 'items.productVariant.variantAttributes.productAttributeOption'])
            ->active()
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => __('messages.error.cart_empty')], 400);
            }

            return redirect()->route('frontend.cart')->with('error', __('messages.error.cart_empty'));
        }

        $hasDeliveryMethod = collect($request->shipping_methods)
            ->contains(fn ($method) => strtoupper((string) ($method['courier_code'] ?? '')) !== CourierCode::PICKUP->value);

        if ($hasDeliveryMethod && ! $request->address_id) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'address_id' => [__('messages.error.select_address_first')],
                ],
            ], 422);
        }

        $address = null;
        if ($request->address_id) {
            $address = CustomerAddress::where('customer_id', $customer->id)
                ->findOrFail($request->address_id);
        } elseif (! $hasDeliveryMethod) {
            $address = CustomerAddress::where('customer_id', $customer->id)
                ->orderBy('is_featured', 'desc')
                ->first();

            if (! $address) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'address_id' => [__('messages.error.select_address_first')],
                    ],
                ], 422);
            }
        } elseif ($hasDeliveryMethod) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'address_id' => [__('messages.error.select_address_first')],
                ],
            ], 422);
        }

        $totalShippingCost = 0;
        $totalWeight = 0;
        $shippingDetails = [];

        foreach ($request->shipping_methods as $warehouseId => $method) {
            $totalShippingCost += $method['price'];
            $totalWeight += $method['weight'];
            $shippingDetails[] = [
                'warehouse_id' => $warehouseId,
                'courier_code' => $method['courier_code'],
                'courier_name' => $method['courier_name'],
                'price' => $method['price'],
                'weight' => $method['weight'],
                'estimation' => $method['estimation'] ?? null,
            ];
        }

        $pendingVouchers = $this->cookieService->get();
        $validatedVouchers = $this->voucherService->validateFromCookie($pendingVouchers, $cart, $customer);

        $subtotal = $cart->items->sum(fn($item) => ($item->price - $item->discount) * $item->quantity);
        $productDiscount = $validatedVouchers['product']['discount_amount'] ?? 0;
        $shippingDiscount = $validatedVouchers['shipping']['discount_amount'] ?? 0;
        $discountedShippingFee = max(0, $totalShippingCost - $shippingDiscount);
        $grandTotal = $subtotal + $discountedShippingFee - $productDiscount;
        $installmentMinOrderAmount = (int) ($generalSettings->installment_min_order_amount ?? 1000000);

        if ($request->payment_type === 'installment' && $grandTotal < $installmentMinOrderAmount) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'payment_type' => ["Minimal belanja untuk cicilan adalah " . number_format($installmentMinOrderAmount, 0, ',', '.')],
                ],
            ], 422);
        }

        if ($request->payment_type === 'full' && ! $this->creditLimitService->canCreateFullBilling($customer, $grandTotal)) {
            return response()->json([
                'success' => false,
                'error' => 'Limit kredit tidak mencukupi',
                'remaining_limit' => $customer->remaining_credit_limit,
                'required' => $grandTotal,
            ], 400);
        }

        if ($request->payment_type === 'installment' && $request->installment_plan_id) {
            $plan = InstallmentPlan::find($request->installment_plan_id);
            $totalWithFee = $plan->calculateTotal($grandTotal);

            if (!$this->creditLimitService->canCreateInstallment($customer, $totalWithFee)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Limit kredit tidak mencukupi',
                    'remaining_limit' => $customer->remaining_credit_limit,
                    'required' => $totalWithFee,
                ], 400);
            }
        }

        $billingCutoffDay = (int) ($generalSettings->billing_cutoff_day ?? 25);
        $billingDueDay = (int) ($generalSettings->billing_due_day ?? 5);
        $billingDueMonthOffset = (int) ($generalSettings->billing_due_month_offset ?? 1);

        return DB::transaction(function () use ($request, $customer, $cart, $totalShippingCost, $totalWeight, $address, $shippingDetails, $validatedVouchers, $paymentGatewayService, $grandTotal, $billingCycleService, $billingCutoffDay, $billingDueDay, $billingDueMonthOffset) {
            $billingDueDate = null;

            if ($request->payment_type === 'full') {
                $cycleMonthKey = $billingCycleService->resolveCycleMonthKey(now(), $billingCutoffDay);
                $billingDueDate = $billingCycleService->resolveDueDate($cycleMonthKey, $billingDueDay, $billingDueMonthOffset);
            }

            $transaction = Transaction::create([
                'customer_id' => $customer->id,
                'customer_address_id' => $address?->id,
                'weight' => $totalWeight,
                'shipping_cost' => $totalShippingCost,
                'payment_method' => $request->payment_type === 'installment' ? 'cicilan' : 'bayar_penuh',
                'payment_type' => $request->payment_type,
                'billing_due_date' => $billingDueDate,
                'billing_status' => $request->payment_type === 'full' ? 'pending' : 'not_applicable',
                'installment_plan_id' => $request->payment_type === 'installment' ? $request->installment_plan_id : null,
                'status' => TransactionStatus::packed,
                'notes' => $request->notes,
                'timelimit' => \Illuminate\Support\Carbon::now()->addDay(),
            ]);

            foreach ($shippingDetails as $detail) {
                $transaction->shippingDetails()->create($detail);
            }

            foreach ($cart->items as $item) {
                $transaction->products()->create([
                    'customer_id' => $customer->id,
                    'is_digital' => false,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->product->warehouse_id ?: 1,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'description' => $item->productVariant?->variant_name,
                ]);
            }

            foreach (['shipping', 'product'] as $type) {
                $voucherData = $validatedVouchers[$type] ?? null;
                if ($voucherData && $voucherData['valid'] ?? false) {
                    $voucher = $this->voucherService->getVoucherByCode($voucherData['code']);
                    if ($voucher) {
                        $transaction->vouchers()->create([
                            'voucher_code' => $voucherData['code'],
                            'voucher_name' => $voucherData['name'],
                            'voucher_type' => $type,
                            'discount_type' => $voucherData['discount_type'],
                            'discount_value' => $voucherData['discount_value'],
                            'discount_amount' => $voucherData['discount_amount'],
                        ]);
                        $this->voucherService->trackUsage($voucher);
                    }
                }
            }

            $this->cookieService->clear();
            $cart->update(['status' => CartStatus::Checked_out]);

            if ($request->payment_type === 'installment' && $request->installment_plan_id) {
                $plan = InstallmentPlan::find($request->installment_plan_id);
                $this->installmentService->createInstallment($transaction, $plan);
            }

            $paymentResponse = $paymentGatewayService->createPayment($transaction);

            // Send payment request notification
            $orderUrl = route('frontend.orders.show', $transaction->uuid);
            SendPaymentRequestNotification::dispatch($transaction, $orderUrl)
                ->onQueue('default');

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'transaction_uuid' => $transaction->uuid,
                    'payment' => [
                        'provider' => $paymentGatewayService->getActiveGatewayAlias(),
                        'payment_url' => $paymentResponse->paymentUrl,
                        'snap_token' => $paymentResponse->metadata['snap_token'] ?? null,
                        'client_key' => $paymentResponse->metadata['client_key'] ?? null,
                    ],
                ]);
            }

            return redirect()->route('frontend.orders.show', $transaction->uuid)->with('success', __('messages.success.order_placed'));
        });
    }
}
