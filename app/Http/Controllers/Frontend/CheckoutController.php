<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Http\Resources\CartResource;
use App\Models\Customer;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkoutService) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $customer = $this->customer();
        $data = $this->checkoutService->pageData($customer, $this->selectedCartItemUuids($request));

        if ($data === null) {
            return redirect()->route('frontend.cart')->with('error', __('messages.error.cart_empty'));
        }

        return Inertia::render('Checkout/Index', [
            'cart' => CartResource::make($data['cart']),
            'cartItemIds' => $data['cartItemIds'],
            'addresses' => AddressResource::collection($data['addresses']),
            'provinces' => $data['provinces'],
            'pendingVouchers' => $data['pendingVouchers'],
            'validatedVouchers' => $data['validatedVouchers'],
            'activeGateway' => $data['activeGateway'],
            'midtransAvailable' => $data['midtransAvailable'],
            'installmentPlans' => $data['installmentPlans'],
            'creditLimit' => $data['creditLimit'],
            'installmentMinOrderAmount' => $data['installmentMinOrderAmount'],
            'balance' => $data['balance'],
        ]);
    }

    public function getShippingCosts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:customer_addresses,id',
        ]);

        try {
            return response()->json($this->checkoutService->shippingCosts(
                $this->customer(),
                (int) $validated['address_id'],
                $this->selectedCartItemUuids($request),
            ));
        } catch (CheckoutException $exception) {
            if ($exception->errorKey !== 'address_no_village') {
                throw $exception;
            }

            return response()->json(['error' => __('messages.error.address_no_village')], 422);
        }
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => 'nullable|exists:customer_addresses,id',
            'shipping_methods' => 'required|array',
            'payment_type' => 'required|in:full,installment,balance',
            'payment_method' => 'nullable|in:midtrans',
            'timezone' => 'nullable|string|max:64',
            'installment_plan_id' => 'nullable|exists:installment_plans,id',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'address_id' => isset($validated['address_id']) ? (int) $validated['address_id'] : null,
            'shipping_methods' => $this->normalizeShippingMethods($validated['shipping_methods']),
            'payment_type' => (string) $validated['payment_type'],
            'payment_method' => isset($validated['payment_method']) ? (string) $validated['payment_method'] : null,
            'timezone' => $request->filled('timezone') ? (string) $validated['timezone'] : null,
            'installment_plan_id' => isset($validated['installment_plan_id']) ? (int) $validated['installment_plan_id'] : null,
            'notes' => isset($validated['notes']) ? (string) $validated['notes'] : null,
            'cart_item_ids' => $this->selectedCartItemUuids($request),
        ];

        try {
            $result = $this->checkoutService->placeOrder($this->customer(), $data);
        } catch (CheckoutException $exception) {
            return $this->failureResponse($request, $exception);
        }

        if ($result->payment['error'] !== null) {
            return response()->json([
                'success' => false,
                'transaction_uuid' => $result->transaction->uuid,
                'error' => __('messages.error.payment_initiation_failed', ['message' => $result->payment['error']]),
            ], 422);
        }

        $payment = $result->payment;
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'transaction_uuid' => $result->transaction->uuid,
                'payment' => [
                    'provider' => $payment['provider'],
                    'payment_url' => $payment['payment_url'],
                    'snap_token' => $payment['snap_token'],
                    'client_key' => $payment['client_key'],
                    'mode' => $payment['mode'],
                ],
            ]);
        }

        return redirect()->route('frontend.orders.show', $result->transaction->uuid)
            ->with('success', __('messages.success.order_placed'));
    }

    /**
     * @return list<string>|null
     */
    private function selectedCartItemUuids(Request $request): ?array
    {
        if (! $request->has('cart_item_ids') || $request->input('cart_item_ids') === null) {
            return null;
        }

        $validated = $request->validate([
            'cart_item_ids' => ['array', 'min:1'],
            'cart_item_ids.*' => ['required', 'uuid', 'distinct'],
        ]);

        return array_values(array_map(
            static fn (mixed $id): string => (string) $id,
            $validated['cart_item_ids'],
        ));
    }

    /**
     * @param  array<string, mixed>  $shippingMethods
     * @return array<string, array{price: float, weight: float, courier_code: string, courier_name: string, estimation: ?string}>
     */
    private function normalizeShippingMethods(array $shippingMethods): array
    {
        $normalized = [];

        foreach ($shippingMethods as $warehouseId => $method) {
            if (! is_array($method)) {
                continue;
            }

            $normalized[(string) $warehouseId] = [
                'price' => (float) ($method['price'] ?? 0),
                'weight' => (float) ($method['weight'] ?? 0),
                'courier_code' => (string) ($method['courier_code'] ?? ''),
                'courier_name' => (string) ($method['courier_name'] ?? ''),
                'estimation' => isset($method['estimation']) ? (string) $method['estimation'] : null,
            ];
        }

        return $normalized;
    }

    private function failureResponse(Request $request, CheckoutException $exception): JsonResponse|RedirectResponse
    {
        $context = $exception->context;

        return match ($exception->errorKey) {
            'midtrans_unavailable' => response()->json(['error' => 'Midtrans tidak tersedia saat ini.'], 422),
            'midtrans_only_full' => response()->json(['error' => 'Midtrans hanya tersedia untuk pembayaran penuh.'], 422),
            'payment_initiation_failed' => response()->json([
                'success' => false,
                'transaction_uuid' => $context['transaction_uuid'] ?? null,
                'error' => __('messages.error.payment_initiation_failed', ['message' => $context['message'] ?? '']),
            ], 422),
            'balance_disabled' => response()->json(['error' => 'Fitur saldo sedang tidak aktif.'], 403),
            'cart_empty' => $request->wantsJson()
                ? response()->json(['error' => __('messages.error.cart_empty')], 400)
                : redirect()->route('frontend.cart')->with('error', __('messages.error.cart_empty')),
            'shipping_methods_mismatch' => response()->json([
                'success' => false,
                'errors' => ['shipping_methods' => [__('messages.error.checkout_failed')]],
            ], 422),
            'address_required' => response()->json([
                'success' => false,
                'errors' => ['address_id' => [__('messages.error.select_address_first')]],
            ], 422),
            'installment_minimum' => response()->json([
                'success' => false,
                'errors' => [
                    'payment_type' => ['Minimal belanja untuk cicilan adalah '.number_format((int) ($context['minimum'] ?? 1000000), 0, ',', '.')],
                ],
            ], 422),
            'balance_insufficient' => response()->json([
                'success' => false,
                'error' => 'Saldo tidak mencukupi',
                'available_balance' => (float) ($context['available_balance'] ?? 0),
                'required' => (float) ($context['required'] ?? 0),
            ], 400),
            'credit_limit_insufficient' => response()->json([
                'success' => false,
                'error' => 'Limit kredit tidak mencukupi',
                'remaining_limit' => (float) ($context['remaining_limit'] ?? 0),
                'required' => (float) ($context['required'] ?? 0),
            ], 400),
            default => throw $exception,
        };
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
