<?php

namespace App\Http\Controllers;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Integration\Services\IntegrationLogService;
use App\Services\Gateways\DTOs\WebhookResult;
use App\Services\PaymentGatewayService;
use App\Services\TransactionCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentGatewayService $paymentGatewayService, IntegrationLogService $integrationLogService)
    {
        $subject = $gateway === 'midtrans'
            ? Transaction::query()->where('uuid', $request->input('order_id'))->first()
            : null;
        $integrationLog = $integrationLogService->start([
            'direction' => IntegrationLog::DIRECTION_INBOUND,
            'provider' => $gateway,
            'type' => IntegrationLog::TYPE_WEBHOOK,
            'method' => $request->method(),
            'url' => $request->url(),
            'endpoint' => '/webhooks/payment/'.$gateway,
            'request_headers' => $request->headers->all(),
            'request_body' => $request->all(),
            'subject' => $subject,
        ]);

        if ($gateway !== 'midtrans' || ! $paymentGatewayService->isGatewayAvailable($gateway)) {
            $integrationLogService->finish($integrationLog, [
                'status' => IntegrationLog::STATUS_FAILED,
                'status_code' => 404,
                'response_body' => ['status' => 'error', 'message' => 'Gateway unavailable'],
                'error_message' => 'Gateway unavailable',
            ]);

            return response()->json(['status' => 'error', 'message' => 'Gateway unavailable'], 404);
        }

        try {
            $result = $paymentGatewayService->handleWebhook($request->all());
            if (! $result->success || $result->action !== WebhookResult::ACTION_PROCESS || ! $result->transactionId) {
                Log::warning('Rejected Midtrans webhook', ['message' => $result->message]);
                $integrationLogService->finish($integrationLog, [
                    'status' => IntegrationLog::STATUS_FAILED,
                    'status_code' => 400,
                    'response_body' => ['status' => 'error', 'message' => $result->message],
                    'error_message' => $result->message,
                ]);

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
                $integrationLogService->finish($integrationLog, [
                    'status' => IntegrationLog::STATUS_FAILED,
                    'status_code' => 400,
                    'response_body' => ['status' => 'error', 'message' => 'Payment amount mismatch'],
                    'error_message' => 'Payment amount mismatch',
                ]);

                return response()->json(['status' => 'error', 'message' => 'Payment amount mismatch'], 400);
            }

            $integrationLogService->finish($integrationLog, [
                'status' => IntegrationLog::STATUS_SUCCESS,
                'status_code' => 200,
                'response_body' => ['status' => 'success'],
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Throwable $exception) {
            Log::error('Midtrans webhook handling failed', ['message' => $exception->getMessage()]);
            $integrationLogService->fail($integrationLog, $exception, 500);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
