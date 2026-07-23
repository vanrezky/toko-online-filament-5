<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\InstallmentStatus;
use App\Enums\TransactionStatus;
use App\Services\PaymentGatewayService;
use App\Services\TransactionCancellationService;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\PaymentGateway;
use App\Http\Resources\OrderResource;
use App\Enums\CourierCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $perPage = (int) $request->integer('per_page', 10);
        if ($perPage <= 0 || $perPage > 50) {
            $perPage = 10;
        }

        $ordersQuery = Transaction::with([
            'products' => function($query) {
                $query->select(
                    'id',
                    'uuid',
                    'transaction_id',
                    'product_id',
                    'warehouse_id',
                    'product_name',
                    'product_code',
                    'variant_name',
                    'variant_sku',
                    'line_subtotal',
                    'quantity',
                    'price',
                    'discount',
                    'description',
                    'product_snapshot'
                );
            }
        ])
        ->where('customer_id', Auth::guard('customer')->id())
        ->orderBy('created_at', 'desc');

        if ($status !== '' && $status !== 'all') {
            $ordersQuery->where('status', $status);
        }

        $orders = $ordersQuery
            ->paginate($perPage, ['id', 'uuid', 'code', 'customer_id', 'status', 'shipping_cost', 'cod_fee', 'created_at', 'timelimit'])
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => OrderResource::collection($orders),
            'activeStatus' => $status === '' ? 'all' : $status,
        ]);
    }

    public function show(Transaction $transaction)
    {
        // Ensure user owns the transaction
        if ($transaction->customer_id !== Auth::guard('customer')->id()) {
            abort(403);
        }

        $transaction->load([
            'shippingDetails.warehouse',
            'shippingDetails.warehouse.village',
            'shippingDetails.warehouse.district',
            'shippingDetails.warehouse.province',
            'vouchers',
            'products.review',
            'products' => function($query) {
                $query->select(
                    'id',
                    'uuid',
                    'transaction_id',
                    'product_id',
                    'warehouse_id',
                    'product_name',
                    'product_code',
                    'variant_name',
                    'variant_sku',
                    'line_subtotal',
                    'quantity',
                    'price',
                    'discount',
                    'description',
                    'product_snapshot'
                );
            }
        ]);

        $hasDelivery = $transaction->shippingDetails
            ->contains(fn ($detail) => strtolower((string) $detail->courier_code) !== CourierCode::PICKUP->value);

        if ($hasDelivery) {
            $transaction->load([
                'address' => function ($query) {
                    $query->select('id', 'name', 'phone', 'address', 'province_id', 'district_id', 'sub_district_id', 'village_id', 'postal_code');
                },
                'address.province:id,name',
                'address.district:id,name',
                'address.subDistrict:id,name',
                'address.village:id,name',
            ]);
        }

        return Inertia::render('Orders/Show', [
            'order' => OrderResource::make($transaction),
        ]);
    }

    public function pay(Transaction $transaction, PaymentGatewayService $paymentGatewayService)
    {
        if ($transaction->customer_id !== Auth::guard('customer')->id()) {
            abort(403);
        }

        if ($transaction->status !== TransactionStatus::packed || $transaction->payment_type !== 'full') {
            return response()->json(['error' => __('messages.error.order_already_paid')], 400);
        }

        try {
            $paymentResponse = $paymentGatewayService->createPayment($transaction);

            if (!$paymentResponse->success) {
                return response()->json(['error' => $paymentResponse->errorMessage], 400);
            }

            return response()->json([
                'success' => true,
                'payment' => [
                    'provider' => $paymentGatewayService->getActiveGatewayAlias(),
                    'payment_url' => $paymentResponse->paymentUrl,
                    'snap_token' => $paymentResponse->metadata['snap_token'] ?? null,
                    'client_key' => $paymentResponse->metadata['client_key'] ?? null,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => __('messages.error.payment_initiation_failed', ['message' => $e->getMessage()])], 500);
        }
    }

    public function cancel(Transaction $transaction, TransactionCancellationService $transactionCancellationService)
    {
        if ($transaction->customer_id !== Auth::guard('customer')->id()) {
            abort(403);
        }

        if ($transaction->status !== TransactionStatus::packed) {
            return back()->with('error', 'Pesanan ini tidak dapat dibatalkan.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction) {
            app(TransactionCancellationService::class)->cancel($transaction);

            // Update installment status if applicable
            if ($transaction->payment_type === 'installment' && $transaction->installment) {
                $transaction->installment->update(['status' => InstallmentStatus::Cancelled->value]);

                $transaction->installment->payments()
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->update(['status' => 'cancelled']);
            }
        });

        return redirect()->route('frontend.orders.show', $transaction->uuid)
            ->with('success', 'Pesanan berhasil dibatalkan.');
    }
}
