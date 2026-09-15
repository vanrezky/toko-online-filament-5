<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): Response
    {
        $customer = $this->customer();
        $status = (string) $request->input('status', 'all');
        $perPage = (int) $request->integer('per_page', 10);
        if ($perPage <= 0 || $perPage > 50) {
            $perPage = 10;
        }
        $orders = $this->orderService->paginate($customer, $status, $perPage);

        return Inertia::render('Orders/Index', [
            'orders' => OrderResource::collection($orders),
            'activeStatus' => $status === '' ? 'all' : $status,
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        $this->ensureOwner($transaction);
        $transaction = $this->orderService->prepareForShow($transaction);

        return Inertia::render('Orders/Show', [
            'order' => OrderResource::make($transaction),
        ]);
    }

    public function pay(Transaction $transaction): JsonResponse
    {
        $this->ensureOwner($transaction);

        if (
            $transaction->status !== TransactionStatus::packed
            || $transaction->payment_type !== 'full'
            || $transaction->payment_method !== 'midtrans'
            || ($transaction->billing_status?->value ?? (string) $transaction->billing_status) !== 'pending'
            || ! $transaction->timelimit
            || $transaction->timelimit->utc()->lte(Carbon::now('UTC')->addMinutes(5))
        ) {
            return response()->json(['error' => __('messages.error.order_already_paid')], 400);
        }

        try {
            $paymentResponse = $this->orderService->initiatePayment($transaction);

            if (! $paymentResponse->success) {
                return response()->json(['error' => $paymentResponse->errorMessage], 400);
            }

            return response()->json([
                'success' => true,
                'payment' => [
                    'provider' => $this->orderService->activeGatewayAlias(),
                    'payment_url' => $paymentResponse->paymentUrl,
                    'snap_token' => $paymentResponse->metadata['snap_token'] ?? null,
                    'client_key' => $paymentResponse->metadata['client_key'] ?? null,
                    'mode' => $paymentResponse->metadata['mode'] ?? null,
                ],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'error' => __('messages.error.payment_initiation_failed', ['message' => $exception->getMessage()]),
            ], 500);
        }
    }

    public function cancel(Transaction $transaction): RedirectResponse
    {
        $this->ensureOwner($transaction);

        if ($transaction->status !== TransactionStatus::packed) {
            return back()->with('error', 'Pesanan ini tidak dapat dibatalkan.');
        }

        $this->orderService->cancel($transaction);

        return redirect()->route('frontend.orders.show', $transaction->uuid)
            ->with('success', 'Pesanan berhasil dibatalkan.');
    }

    private function ensureOwner(Transaction $transaction): void
    {
        abort_unless($transaction->customer_id === Auth::guard('customer')->id(), 403);
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
