<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Transaction;
use App\Repositories\OrderRepository;
use App\Services\Gateways\DTOs\PaymentResponse;
use App\Services\Gateways\DTOs\PaymentStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PaymentGatewayService $paymentGatewayService,
        private readonly TransactionCancellationService $transactionCancellationService,
    ) {}

    public function paginate(Customer $customer, string $status, int $perPage): LengthAwarePaginator
    {
        return $this->orderRepository->paginateForCustomer($customer, $status, $perPage);
    }

    public function prepareForShow(Transaction $transaction): Transaction
    {
        return $this->orderRepository->loadForShow($transaction);
    }

    public function initiatePayment(Transaction $transaction): PaymentResponse
    {
        return $this->paymentGatewayService->createPayment($transaction);
    }

    public function synchronizePaymentStatus(Transaction $transaction): ?PaymentStatus
    {
        $billingStatus = $transaction->billing_status?->value ?? (string) $transaction->billing_status;
        $transactionStatus = $transaction->status?->value ?? (string) $transaction->status;

        if ($transaction->payment_method !== 'midtrans'
            || ! $this->paymentGatewayService->isGatewayAvailable('midtrans')
            || in_array($billingStatus, [TransactionBillingStatus::paid->value, TransactionBillingStatus::cancelled->value], true)
            || $transactionStatus === TransactionStatus::cancelled->value) {
            return null;
        }

        $paymentStatus = $this->paymentGatewayService->getPaymentStatus((string) $transaction->uuid);

        if ($paymentStatus->transactionId !== (string) $transaction->uuid
            || $paymentStatus->amount === null
            || (int) round($paymentStatus->amount) !== (int) round((float) $transaction->total_amount)) {
            return $paymentStatus;
        }

        DB::transaction(function () use ($transaction, $paymentStatus): void {
            $lockedTransaction = Transaction::query()
                ->lockForUpdate()
                ->find($transaction->getKey());

            if (! $lockedTransaction) {
                return;
            }

            $billingStatus = $lockedTransaction->billing_status?->value ?? (string) $lockedTransaction->billing_status;
            $transactionStatus = $lockedTransaction->status?->value ?? (string) $lockedTransaction->status;

            if (in_array($billingStatus, [TransactionBillingStatus::paid->value, TransactionBillingStatus::cancelled->value], true)
                || $transactionStatus === TransactionStatus::cancelled->value) {
                return;
            }

            if ($paymentStatus->status === 'success') {
                $lockedTransaction->update([
                    'billing_status' => TransactionBillingStatus::paid->value,
                ]);
            } elseif ($paymentStatus->status === 'pending') {
                $lockedTransaction->update([
                    'billing_status' => TransactionBillingStatus::pending->value,
                ]);
            } elseif ($paymentStatus->status === 'failed') {
                $lockedTransaction->update([
                    'billing_status' => TransactionBillingStatus::failed->value,
                ]);
            } elseif (in_array($paymentStatus->status, ['expired', 'cancelled'], true)) {
                $this->transactionCancellationService->cancel($lockedTransaction);
            }
        });

        return $paymentStatus;
    }

    public function activeGatewayAlias(): ?string
    {
        return $this->paymentGatewayService->getActiveGatewayAlias();
    }

    public function cancel(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $this->transactionCancellationService->cancel($transaction);
            $this->orderRepository->cancelInstallment($transaction);
        });
    }
}
