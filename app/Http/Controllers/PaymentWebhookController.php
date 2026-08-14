<?php

namespace App\Http\Controllers;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Services\Gateways\DTOs\WebhookResult;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use App\Services\TransactionCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentGatewayService $paymentGatewayService)
    {
        if ($gateway !== 'midtrans' || ! $paymentGatewayService->isGatewayAvailable($gateway)) {
            return response()->json(['status' => 'error', 'message' => 'Gateway unavailable'], 404);
        }

        try {
            $result = $paymentGatewayService->handleWebhook($request->all());
            if (! $result->success || $result->action !== WebhookResult::ACTION_PROCESS || ! $result->transactionId) {
                Log::warning('Rejected Midtrans webhook', ['message' => $result->message]);

                return response()->json(['status' => 'error', 'message' => $result->message], 400);
            }

            $outcome = DB::transaction(function () use ($result, $gateway) {
                $transaction = Transaction::query()
                    ->with(['products', 'vouchers'])
                    ->where('uuid', $result->transactionId)
                    ->lockForUpdate()
                    ->first();

                if (! $transaction || $transaction->payment_method !== $gateway) {
                    return 'ignored';
                }

                if ((int) round($transaction->total_amount) !== (int) ($result->metadata['gross_amount'] ?? -1)) {
                    return 'amount_mismatch';
                }

                $billingStatus = $transaction->billing_status?->value ?? (string) $transaction->billing_status;
                $transactionStatus = $transaction->status?->value ?? (string) $transaction->status;
                if (in_array($billingStatus, [TransactionBillingStatus::paid->value, TransactionBillingStatus::cancelled->value], true)
                    || $transactionStatus === TransactionStatus::cancelled->value) {
                    return 'noop';
                }

                $fraudStatus = $result->metadata['fraud_status'] ?? 'accept';
                if ($result->status === 'success') {
                    if (($result->metadata['status_code'] ?? '') !== '200' || ! in_array($fraudStatus, ['accept', ''], true)) {
                        return 'rejected';
                    }
                    $transaction->update(['billing_status' => TransactionBillingStatus::paid->value]);
                    return 'paid';
                }

                if ($result->status === 'pending') {
                    $transaction->update(['billing_status' => TransactionBillingStatus::pending->value]);
                    return 'pending';
                }

                if ($result->status === 'failed') {
                    $transaction->update(['billing_status' => TransactionBillingStatus::failed->value]);
                    return 'failed';
                }

                if (in_array($result->status, ['expired', 'cancelled'], true)) {
                    app(TransactionCancellationService::class)->cancel($transaction);
                    return 'cancelled';
                }

                return 'noop';
            });

            if ($outcome === 'amount_mismatch') {
                return response()->json(['status' => 'error', 'message' => 'Payment amount mismatch'], 400);
            }

            return response()->json(['status' => 'success']);
        } catch (\Throwable $exception) {
            Log::error('Midtrans webhook handling failed', ['message' => $exception->getMessage()]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
