<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Installment;
use App\Services\InstallmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstallmentController extends Controller
{
    public function __construct(
        protected InstallmentService $installmentService
    ) {}

    public function index(Request $request)
    {
        $customer = auth('customer')->user();

        $installments = $this->installmentService->getCustomerInstallments($customer);

        return Inertia::render('Frontend/Installment/Index', [
            'installments' => $installments->map(function ($installment) {
                return [
                    'uuid' => $installment->uuid,
                    'transaction' => [
                        'uuid' => $installment->transaction->uuid,
                        'created_at' => $installment->transaction->created_at,
                    ],
                    'total_amount' => $installment->total_amount,
                    'monthly_amount' => $installment->monthly_amount,
                    'tenor' => $installment->tenor,
                    'paid_installments' => $installment->paid_installments,
                    'status' => $installment->status,
                    'start_date' => $installment->start_date,
                    'expected_end_date' => $installment->expected_end_date,
                ];
            }),
        ]);
    }

    public function show(Request $request, string $uuid)
    {
        $installment = Installment::where('uuid', $uuid)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        $schedule = $this->installmentService->getInstallmentSchedule($installment);

        return Inertia::render('Frontend/Installment/Show', [
            'installment' => [
                'uuid' => $installment->uuid,
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
            'schedule' => $schedule->map(function ($payment) {
                return [
                    'installment_number' => $payment->installment_number,
                    'amount' => $payment->amount,
                    'due_date' => $payment->due_date,
                    'paid_amount' => $payment->paid_amount,
                    'paid_date' => $payment->paid_date,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                ];
            }),
        ]);
    }
}