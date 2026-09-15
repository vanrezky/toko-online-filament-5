<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Installment;
use App\Services\InstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class InstallmentController extends Controller
{
    public function __construct(private readonly InstallmentService $installmentService) {}

    public function index(Request $request): Response
    {
        $customer = $this->customer();
        $installments = $this->installmentService->getCustomerInstallments($customer);

        return Inertia::render('Frontend/Installment/Index', [
            'installments' => $installments->map(function (Installment $installment): array {
                return [
                    'uuid' => $installment->uuid,
                    'code' => $installment->code,
                    'transaction' => [
                        'uuid' => $installment->transaction->uuid,
                        'code' => $installment->transaction->code,
                        'created_at' => $installment->transaction->created_at,
                    ],
                    'status' => $installment->status,
                    'total_amount' => $installment->total_amount,
                    'monthly_amount' => $installment->monthly_amount,
                    'tenor' => $installment->tenor,
                    'paid_installments' => $installment->paid_installments,
                    'start_date' => $installment->start_date,
                    'expected_end_date' => $installment->expected_end_date,
                ];
            }),
            'monthlyBills' => $this->installmentService->getMonthlyBills($customer),
        ]);
    }

    public function show(Request $request, string $uuid): Response
    {
        $installment = $this->installmentService->findForCustomer($this->customer(), $uuid);
        $schedule = $this->installmentService->getInstallmentSchedule($installment);

        return Inertia::render('Frontend/Installment/Show', [
            'installment' => [
                'uuid' => $installment->uuid,
                'code' => $installment->code,
                'status' => $installment->status,
                'principal_amount' => $installment->principal_amount,
                'fee_amount' => $installment->fee_amount,
                'total_amount' => $installment->total_amount,
                'monthly_amount' => $installment->monthly_amount,
                'tenor' => $installment->tenor,
                'paid_installments' => $installment->paid_installments,
                'paid_amount' => $installment->paid_amount,
                'start_date' => $installment->start_date,
                'expected_end_date' => $installment->expected_end_date,
                'installment_plan' => [
                    'tenor' => $installment->installmentPlan->tenor,
                    'fee_percentage' => $installment->installmentPlan->fee_percentage,
                ],
            ],
            'schedule' => $schedule->map(static fn ($payment): array => [
                'code' => $payment->code,
                'installment_number' => $payment->installment_number,
                'amount' => $payment->amount,
                'due_date' => $payment->due_date,
                'billing_month' => $payment->billing_month,
                'paid_amount' => $payment->paid_amount,
                'paid_date' => $payment->paid_date,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'payroll_status' => $payment->payroll_status,
            ]),
        ]);
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
