<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use App\Repositories\OrderRepository;
use App\Services\Gateways\DTOs\PaymentResponse;
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
