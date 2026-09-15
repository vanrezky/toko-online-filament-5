<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CourierCode;
use App\Enums\TransactionStatus;
use App\Exceptions\CheckoutException;
use App\Jobs\SendPaymentRequestNotification;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\InstallmentPlan;
use App\Models\Transaction;
use App\Repositories\CheckoutRepository;
use App\Settings\CourierSettings;
use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CheckoutService
{
    public function __construct(
        private readonly VoucherCookieService $cookieService,
        private readonly VoucherService $voucherService,
        private readonly InstallmentService $installmentService,
        private readonly CreditLimitService $creditLimitService,
        private readonly TransactionProductImageSnapshotService $transactionProductImageSnapshotService,
        private readonly FlashsalePricingService $flashsalePricingService,
        private readonly FlashsaleReservationService $flashsaleReservationService,
        private readonly ProductInventoryService $productInventoryService,
        private readonly BalanceService $balanceService,
        private readonly RegionalService $regionalService,
        private readonly CheckoutRepository $checkoutRepository,
        private readonly ApicoidOngkirService $ongkirService,
        private readonly CourierSettings $courierSettings,
        private readonly GeneralSettings $generalSettings,
        private readonly BillingCycleService $billingCycleService,
        private readonly PaymentGatewayService $paymentGatewayService,
    ) {}

    /**
     * @return array{
     *     cart: Cart,
     *     cartItemIds: list<string>|null,
     *     addresses: Collection<int, CustomerAddress>,
     *     provinces: Collection<int, array{id: int, name: string}>,
     *     pendingVouchers: array<string, array<string, int|float|string|bool|null>|null>,
     *     validatedVouchers: array<string, array<string, int|float|string|bool|null>|null>,
     *     activeGateway: ?string,
     *     midtransAvailable: bool,
     *     installmentPlans: Collection<int, InstallmentPlan>,
     *     creditLimit: array{remaining: float|int|string, effective: float|int|string, enforced: bool},
     *     installmentMinOrderAmount: int,
     *     balance: array{enabled: bool, available: float}
     * }|null
     */
    public function pageData(Customer $customer, ?array $cartItemUuids): ?array
    {
        $cart = $this->checkoutRepository->activeCart($customer);

        if ($cart !== null) {
            $this->scopeCartItems($cart, $cartItemUuids);
        }

        if ($cart === null || $cart->items->isEmpty()) {
            return null;
        }

        $this->flashsalePricingService->syncCart($cart);
        $addresses = $this->checkoutRepository->addresses($customer);
        $pendingVouchers = $this->cookieService->get();
        $validatedVouchers = $this->voucherService->validateFromCookie($pendingVouchers, $cart, $customer);

        return [
            'cart' => $cart,
            'cartItemIds' => $cartItemUuids,
            'addresses' => $addresses,
            'provinces' => $this->regionalService->getProvinces()->map(
                fn($province): array => ['id' => (int) $province->id, 'name' => (string) $province->name],
            ),
            'pendingVouchers' => $pendingVouchers,
            'validatedVouchers' => $validatedVouchers,
            'activeGateway' => $this->paymentGatewayService->getActiveGatewayAlias(),
            'midtransAvailable' => $this->paymentGatewayService->isGatewayAvailable('midtrans'),
            'installmentPlans' => $this->checkoutRepository->activeInstallmentPlans(),
            'creditLimit' => [
                'remaining' => $customer->remaining_credit_limit,
                'effective' => $customer->effective_credit_limit,
                'enforced' => $this->creditLimitService->shouldEnforceLimit(),
            ],
            'installmentMinOrderAmount' => (int) ($this->generalSettings->installment_min_order_amount ?? 1000000),
            'balance' => [
                'enabled' => (bool) $this->generalSettings->balance_enabled,
                'available' => (float) $customer->balance,
            ],
        ];
    }

    private function scopeCartItems(Cart $cart, ?array $cartItemUuids): Cart
    {
        if ($cartItemUuids === null) {
            return $cart;
        }

        $items = $cart->items->whereIn('uuid', $cartItemUuids)->values();

        if ($items->count() !== count($cartItemUuids)) {
            throw ValidationException::withMessages([
                'cart_item_ids' => [__('messages.error.cart_empty')],
            ]);
        }

        return $cart->setRelation('items', $items);
    }

    private function roundIdr(float|int|string $amount): int
    {
        return (int) round((float) $amount, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * @param  Collection<int, CartItem|array{item: CartItem, pricing: array{price: float|int|string, original_price: float|int|string}}>  $items
     * @param  array<string, array<string, int|float|string|bool|null>|null>  $validatedVouchers
     * @param  Closure(CartItem|array{item: CartItem, pricing: array{price: float|int|string, original_price: float|int|string}}): array{key: int|string, quantity: int, original_price: float|int|string, final_price: float|int|string}  $pricing
     * @return array{lines: array<int|string, array{price: int, discount: int, line_subtotal: int}>, shipping_cost: int, product_voucher_discount: int, shipping_voucher_discount: int, total: int}
     */
    private function calculateRoundedAmounts(Collection $items, float|int $shippingCost, array $validatedVouchers, Closure $pricing): array
    {
        $lines = [];
        $productTotal = 0;

        foreach ($items as $item) {
            $amounts = $pricing($item);
            $quantity = $amounts['quantity'];
            $originalUnitPrice = $this->roundIdr($amounts['original_price']);
            $finalUnitPrice = $this->roundIdr($amounts['final_price']);
            $lineSubtotal = $finalUnitPrice * $quantity;

            $lines[$amounts['key']] = [
                'price' => $originalUnitPrice,
                'discount' => max(0, $originalUnitPrice - $finalUnitPrice),
                'line_subtotal' => $lineSubtotal,
            ];
            $productTotal += $lineSubtotal;
        }

        $roundedShippingCost = $this->roundIdr($shippingCost);
        $productVoucherDiscount = min($productTotal, $this->roundIdr($validatedVouchers['product']['discount_amount'] ?? 0));
        $shippingVoucherDiscount = min($roundedShippingCost, $this->roundIdr($validatedVouchers['shipping']['discount_amount'] ?? 0));

        return [
            'lines' => $lines,
            'shipping_cost' => $roundedShippingCost,
            'product_voucher_discount' => $productVoucherDiscount,
            'shipping_voucher_discount' => $shippingVoucherDiscount,
            'total' => $productTotal - $productVoucherDiscount + $roundedShippingCost - $shippingVoucherDiscount,
        ];
    }

    /**
     * @return list<array{warehouse_id: int|string, warehouse_name: string, weight: float|int, options: list<array<string, int|float|string|null>>}>
     */
    public function shippingCosts(Customer $customer, int $addressId, ?array $cartItemUuids): array
    {
        $cart = $this->checkoutRepository->checkoutCart($customer);

        $this->scopeCartItems($cart, $cartItemUuids);

        $address = $this->checkoutRepository->addressWithVillage($customer, $addressId);

        if (! $address->village) {
            throw new CheckoutException('address_no_village');
        }

        $cartHash = md5($cart->items->sortBy('id')->map(
            fn(CartItem $item): string => $item->product_id . '-' . ($item->product_variant_id ?? '0') . '-' . $item->quantity . '-' . $item->price,
        )->implode('|'));

        $shippingCostsVersion = (int) Cache::get('shipping_costs_version', 1);
        $cacheKey = "shipping_costs_v{$shippingCostsVersion}_{$customer->id}_{$address->id}_{$cartHash}";
        $shippingResults = CacheService::rememberManaged('shipping', $cacheKey, 1800, function () use ($cart, $address): array {
            $warehouseGroups = $cart->items->groupBy(
                fn(CartItem $item): int => (int) ($item->product->warehouse_id ?: 0),
            );

            $activeCouriers = $this->checkoutRepository->activeCouriers();
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
                'price' => $this->courierSettings->kurir_toko_price,
                'estimation' => __('labels.shipping.estimation_1_2_days'),
            ] : null;

            $results = [];

            foreach ($warehouseGroups as $warehouseId => $items) {
                $warehouse = $this->checkoutRepository->warehouseWithVillage((int) $warehouseId);
                if (! $warehouse || ! $warehouse->village) {
                    continue;
                }

                $totalWeight = $items->sum(
                    fn(CartItem $item): float|int => ($item->productVariant?->weight ?: $item->product->weight) * $item->quantity,
                );

                $costs = $this->ongkirService->getShippingCost(
                    $warehouse->village->apicoid_code,
                    $address->village->apicoid_code,
                    $totalWeight,
                );


                $options = [];
                if ($costs['is_success'] && !empty($costs['data']['couriers'])) {
                    foreach ($costs['data']['couriers'] as $courier) {
                        $options[] = [
                            'courier_code' => $courier['courier_code'],
                            'courier_name' => $courier['courier_name'],
                            'price' => $courier['price'],
                            'estimation' => $courier['estimation'],
                        ];
                    }
                }

                if ($kurirTokoOption) {
                    $options[] = $kurirTokoOption;
                }

                if ($pickupOption) {
                    $options[] = $pickupOption;
                }

                if ($options !== []) {
                    $results[] = [
                        'warehouse_id' => $warehouseId,
                        'warehouse_name' => (string) $warehouse->name,
                        'weight' => $totalWeight,
                        'options' => $options,
                    ];
                }
            }

            return $results;
        });

        return is_array($shippingResults) ? $shippingResults : [];
    }

    /**
     * @param array{
     *     address_id: int|null,
     *     shipping_methods: array<string, array{price: float, weight: float, courier_code: string, courier_name: string, estimation: ?string}>,
     *     payment_type: 'full'|'installment'|'balance',
     *     payment_method: ?string,
     *     timezone: ?string,
     *     installment_plan_id: int|null,
     *     notes: ?string,
     *     cart_item_ids: list<string>|null
     * } $data
     */
    public function placeOrder(Customer $customer, array $data): CheckoutResult
    {

        $isMidtransPayment = $data['payment_method'] === 'midtrans';
        $customerTimezone = $customer->timezone ?: config('app.timezone', 'UTC');

        if ($data['timezone'] !== null) {
            $customerTimezone = (string) $data['timezone'];
        }

        if (! in_array($customerTimezone, timezone_identifiers_list(), true)) {
            throw ValidationException::withMessages(['timezone' => ['Zona waktu tidak valid.']]);
        }

        if ($isMidtransPayment && ! $this->paymentGatewayService->isGatewayAvailable('midtrans')) {
            throw new CheckoutException('midtrans_unavailable');
        }

        if ($isMidtransPayment && $data['payment_type'] !== 'full') {
            throw new CheckoutException('midtrans_only_full');
        }
        $cartItemUuids = $data['cart_item_ids'];

        if ($data['payment_type'] === 'balance' && ! $this->generalSettings->balance_enabled) {
            throw new CheckoutException('balance_disabled');
        }
        $cart = $this->checkoutRepository->checkoutCart($customer);
        $this->scopeCartItems($cart, $cartItemUuids);

        if ($cart->items->isEmpty()) {
            throw new CheckoutException('cart_empty');
        }

        // Cart values are only a cache. Refresh them before any total is shown or validated.
        $this->flashsalePricingService->syncCart($cart);

        $cartWarehouseIds = $cart->items
            ->map(fn($item) => (string) ($item->product->warehouse_id ?: 0))
            ->unique()
            ->sort()
            ->values();
        $shippingWarehouseIds = collect(array_keys($data['shipping_methods']))
            ->map(fn($warehouseId) => (string) $warehouseId)
            ->unique()
            ->sort()
            ->values();

        if ($cartWarehouseIds->all() !== $shippingWarehouseIds->all()) {
            throw new CheckoutException('shipping_methods_mismatch');
        }

        $hasDeliveryMethod = collect($data['shipping_methods'])
            ->contains(fn($method) => strtoupper((string) ($method['courier_code'] ?? '')) !== CourierCode::PICKUP->value);

        if ($hasDeliveryMethod && ! $data['address_id']) {
            throw new CheckoutException('address_required');
        }

        $address = null;
        if ($data['address_id']) {
            $address = $this->checkoutRepository->address($customer, (int) $data['address_id']);
        } elseif (! $hasDeliveryMethod) {
            $address = $this->checkoutRepository->addresses($customer)->first();

            if (! $address) {
                throw new CheckoutException('address_required');
            }
        } elseif ($hasDeliveryMethod) {
            throw new CheckoutException('address_required');
        }

        $totalShippingCost = 0;
        $totalWeight = 0;
        $shippingDetails = [];

        foreach ($data['shipping_methods'] as $warehouseId => $method) {
            $roundedShippingPrice = $this->roundIdr($method['price']);
            $totalShippingCost += $roundedShippingPrice;
            $totalWeight += $method['weight'];
            $shippingDetails[] = [
                'warehouse_id' => $warehouseId,
                'courier_code' => $method['courier_code'],
                'courier_name' => $method['courier_name'],
                'price' => $roundedShippingPrice,
                'weight' => $method['weight'],
                'estimation' => $method['estimation'] ?? null,
            ];
        }

        $pendingVouchers = $this->cookieService->get();
        $validatedVouchers = $this->voucherService->validateFromCookie($pendingVouchers, $cart, $customer);

        $roundedAmounts = $this->calculateRoundedAmounts(
            $cart->items,
            $totalShippingCost,
            $validatedVouchers,
            fn(CartItem $item) => [
                'key' => $item->id,
                'quantity' => $item->quantity,
                'original_price' => (float) $item->price + (float) $item->discount,
                'final_price' => $item->price,
            ],
        );
        $grandTotal = $roundedAmounts['total'];
        $installmentMinOrderAmount = (int) ($this->generalSettings->installment_min_order_amount ?? 1000000);

        if ($data['payment_type'] === 'installment' && $grandTotal < $installmentMinOrderAmount) {
            throw new CheckoutException('installment_minimum', ['minimum' => $installmentMinOrderAmount]);
        }

        if ($data['payment_type'] === 'balance' && (float) $customer->balance < $grandTotal) {
            throw new CheckoutException('balance_insufficient', ['available_balance' => (float) $customer->balance, 'required' => $grandTotal]);
        }

        if (! $isMidtransPayment && $data['payment_type'] === 'full' && ! $this->creditLimitService->canCreateFullBilling($customer, $grandTotal)) {
            throw new CheckoutException('credit_limit_insufficient', ['remaining_limit' => (float) $customer->remaining_credit_limit, 'required' => $grandTotal]);
        }

        if ($data['payment_type'] === 'installment' && $data['installment_plan_id']) {
            $plan = $this->checkoutRepository->findPlanOrFail((int) $data['installment_plan_id']);
            $totalWithFee = $plan->calculateTotal($grandTotal);

            if (! $this->creditLimitService->canCreateInstallment($customer, $totalWithFee)) {
                throw new CheckoutException('credit_limit_insufficient', ['remaining_limit' => (float) $customer->remaining_credit_limit, 'required' => $totalWithFee]);
            }
        }

        $billingCutoffDay = (int) ($this->generalSettings->billing_cutoff_day ?? 25);
        $billingDueDay = (int) ($this->generalSettings->billing_due_day ?? 5);
        $billingDueMonthOffset = (int) ($this->generalSettings->billing_due_month_offset ?? 1);

        $transaction = DB::transaction(function () use ($customer, $customerTimezone, $cartItemUuids, $totalShippingCost, $totalWeight, $address, $shippingDetails, $billingCutoffDay, $billingDueDay, $billingDueMonthOffset, $isMidtransPayment, $data): Transaction {
            $lockedCart = $this->checkoutRepository->lockCart($customer);
            $this->scopeCartItems($lockedCart, $cartItemUuids);

            // Recalculate while product_flashsales rows are locked. This is the final authority.
            $resolvedCartItems = $this->flashsalePricingService->resolveCart($lockedCart, true);
            foreach ($resolvedCartItems as $entry) {
                $this->checkoutRepository->updateCartItemPricing($entry['item'], [

                    'price' => $entry['pricing']['price'],

                    'discount' => $entry['pricing']['discount'],

                ]);
            }

            $pendingVouchers = $this->cookieService->get();
            $validatedVouchers = $this->voucherService->validateFromCookie($pendingVouchers, $lockedCart, $customer);
            $roundedAmounts = $this->calculateRoundedAmounts(
                $resolvedCartItems,
                $totalShippingCost,
                $validatedVouchers,
                fn(array $entry) => [
                    'key' => $entry['item']->id,
                    'quantity' => $entry['item']->quantity,
                    'original_price' => $entry['pricing']['original_price'],
                    'final_price' => $entry['pricing']['price'],
                ],
            );
            $grandTotal = $roundedAmounts['total'];

            $installmentMinOrderAmount = (int) ($this->generalSettings->installment_min_order_amount ?? 1000000);
            if ($data['payment_type'] === 'installment' && $grandTotal < $installmentMinOrderAmount) {
                throw new CheckoutException('installment_minimum', ['minimum' => $installmentMinOrderAmount]);
            }

            if ($data['payment_type'] === 'balance' && ! $this->generalSettings->balance_enabled) {
                throw new CheckoutException('balance_disabled');
            }

            if (! $isMidtransPayment && $data['payment_type'] === 'full' && ! $this->creditLimitService->canCreateFullBilling($customer, $grandTotal)) {
                throw new CheckoutException('credit_limit_insufficient', ['remaining_limit' => (float) $customer->remaining_credit_limit, 'required' => $grandTotal]);
            }

            if ($data['payment_type'] === 'installment' && $data['installment_plan_id']) {
                $plan = $this->checkoutRepository->findPlanOrFail((int) $data['installment_plan_id']);
                $totalWithFee = $plan->calculateTotal($grandTotal);
                if (! $this->creditLimitService->canCreateInstallment($customer, $totalWithFee)) {
                    throw new CheckoutException('credit_limit_insufficient', ['remaining_limit' => (float) $customer->remaining_credit_limit, 'required' => $totalWithFee]);
                }
            }

            $billingDueDate = null;

            if ($data['payment_type'] === 'full' && ! $isMidtransPayment) {
                $cycleMonthKey = $this->billingCycleService->resolveCycleMonthKey(now(), $billingCutoffDay);
                $billingDueDate = $this->billingCycleService->resolveDueDate($cycleMonthKey, $billingDueDay, $billingDueMonthOffset);
            }

            $transaction = $this->checkoutRepository->createTransaction([
                'customer_id' => $customer->id,
                'customer_address_id' => $address?->id,
                'weight' => $totalWeight,
                'shipping_cost' => $roundedAmounts['shipping_cost'],
                'payment_method' => match ($data['payment_type']) {
                    'installment' => 'cicilan',
                    'balance' => 'saldo',
                    default => $isMidtransPayment ? 'midtrans' : 'bayar_penuh',
                },
                'payment_type' => $data['payment_type'],
                'billing_due_date' => $billingDueDate,
                'billing_status' => match ($data['payment_type']) {
                    'full' => 'pending',
                    'balance' => 'paid',
                    default => 'not_applicable',
                },
                'installment_plan_id' => $data['payment_type'] === 'installment' ? $data['installment_plan_id'] : null,
                'status' => TransactionStatus::packed,
                'notes' => $data['notes'],
                'timelimit' => Carbon::now('UTC')->addMinutes((int) ($this->generalSettings->transaction_time_limit_minutes ?? 1440)),
                'customer_timezone' => $customerTimezone,
            ]);

            if ($customer->timezone !== $customerTimezone) {
                $this->checkoutRepository->updateCustomerTimezone($customer, $customerTimezone);
            }

            foreach ($shippingDetails as $detail) {
                $this->checkoutRepository->createShippingDetail($transaction, $detail);
            }

            $this->flashsaleReservationService->reserve($transaction, $resolvedCartItems);

            foreach ($resolvedCartItems as $entry) {
                $item = $entry['item'];
                $roundedLine = $roundedAmounts['lines'][$item->id];
                $variant = $item->productVariant;
                $product = $item->product;
                $imageSnapshot = $this->transactionProductImageSnapshotService->snapshotFeaturedImage($product);

                $this->checkoutRepository->createTransactionProduct($transaction, [
                    'customer_id' => $customer->id,
                    'is_digital' => (bool) ($product->digital ?? false),
                    'product_id' => $item->product_id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'variant_name' => $variant?->variant_name,
                    'variant_sku' => $variant?->sku,
                    'weight_snapshot' => $variant?->weight ?: $product->weight,
                    'warehouse_id' => $item->product->warehouse_id ?: 1,
                    'quantity' => $item->quantity,
                    'price' => $roundedLine['price'],
                    'discount' => $roundedLine['discount'],
                    'line_subtotal' => $roundedLine['line_subtotal'],
                    'description' => $variant?->variant_name,
                    'product_snapshot' => [
                        'product_name' => $product->name,
                        'product_code' => $product->code,
                        'variant_name' => $variant?->variant_name,
                        'variant_sku' => $variant?->sku,
                        'weight' => $variant?->weight ?: $product->weight,
                        'is_digital' => (bool) ($product->digital ?? false),
                        'featured_image_path' => $imageSnapshot['featured_image_path'],
                        'featured_image_url' => $imageSnapshot['featured_image_url'],
                    ],
                ]);
            }

            $this->productInventoryService->reserve($transaction, $resolvedCartItems);

            foreach (['shipping', 'product'] as $type) {
                $voucherData = $validatedVouchers[$type] ?? null;
                if ($voucherData && ($voucherData['valid'] ?? false)) {
                    $voucher = $this->voucherService->getVoucherByCode($voucherData['code']);
                    if ($voucher) {
                        $this->checkoutRepository->createTransactionVoucher($transaction, [
                            'voucher_code' => $voucherData['code'],
                            'voucher_name' => $voucherData['name'],
                            'voucher_type' => $type,
                            'discount_type' => $voucherData['discount_type'],
                            'discount_value' => $voucherData['discount_value'],
                            'discount_amount' => $type === 'product'
                                ? $roundedAmounts['product_voucher_discount']
                                : $roundedAmounts['shipping_voucher_discount'],
                        ]);
                        $this->voucherService->trackUsage($voucher);
                    }
                }
            }

            $this->cookieService->clear();
            $this->checkoutRepository->deleteCartItems($resolvedCartItems->pluck('item.id')->map(static fn(int|string $id): int => (int) $id)->all());

            if (! $this->checkoutRepository->cartHasItems($lockedCart)) {
                $this->checkoutRepository->markCartCheckedOut($lockedCart);
            }

            if ($data['payment_type'] === 'installment' && $data['installment_plan_id']) {
                $plan = $this->checkoutRepository->findPlanOrFail((int) $data['installment_plan_id']);
                $this->installmentService->createInstallment($transaction, $plan);
            }

            if ($data['payment_type'] === 'balance') {
                $this->balanceService->pay($transaction);
            }

            // Send payment request notification
            $orderUrl = route('frontend.orders.show', $transaction->uuid);
            SendPaymentRequestNotification::dispatch($transaction, $orderUrl)
                ->onQueue('default');

            return $transaction;
        });

        $payment = [
            'provider' => $data['payment_type'],
            'payment_url' => null,
            'snap_token' => null,
            'client_key' => null,
            'mode' => null,
            'error' => null,
        ];

        if ($isMidtransPayment) {
            $paymentResponse = $this->paymentGatewayService->createPayment($transaction);
            if (! $paymentResponse->success) {
                throw new CheckoutException('payment_initiation_failed', ['transaction_uuid' => (string) $transaction->uuid, 'message' => (string) $paymentResponse->errorMessage]);
            }

            $payment = [
                'provider' => 'midtrans',
                'payment_url' => $paymentResponse->paymentUrl,
                'snap_token' => $paymentResponse->metadata['snap_token'] ?? null,
                'client_key' => $paymentResponse->metadata['client_key'] ?? null,
                'mode' => $paymentResponse->metadata['mode'] ?? null,
                'error' => null,
            ];
        }

        return new CheckoutResult($transaction, $payment);
    }
}
