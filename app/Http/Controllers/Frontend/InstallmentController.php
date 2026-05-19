<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Installment;
use App\Models\Transaction;
use App\Services\InstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
                    'code' => $installment->code,
                    'transaction' => [
                        'uuid' => $installment->transaction->uuid,
                        'code' => $installment->transaction->code,
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
            'monthlyBills' => $this->getMonthlyBills($customer->id),
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
            'schedule' => $schedule->map(function ($payment) {
                return [
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
                ];
            }),
        ]);
    }

    private function getMonthlyBills(int $customerId): array
    {
        $installmentPayments = Installment::query()
            ->where('customer_id', $customerId)
            ->with([
                'transaction.products.product:id,name',
                'payments' => function ($query) {
                    $query->whereIn('status', ['unpaid', 'partial', 'overdue'])
                        ->orderBy('due_date');
                },
            ])
            ->get()
            ->flatMap(function (Installment $installment) {
                $productName = $installment->transaction->products->first()?->product?->name ?? 'Produk';

                return $installment->payments->map(function ($payment) use ($installment, $productName) {
                    $month = Carbon::parse($payment->billing_month ?? $payment->due_date);

                    return [
                        'month_key' => $month->format('Y-m'),
                        'month_label' => $month->translatedFormat('F Y'),
                        'description' => sprintf('Cicilan ke-%d %s', $payment->installment_number, $productName),
                        'amount' => (float) $payment->amount,
                        'status' => $payment->status,
                        'reference' => $installment->code,
                    ];
                });
            });

        $fullBills = Transaction::query()
            ->where('customer_id', $customerId)
            ->where('payment_type', 'full')
            ->whereIn('billing_status', ['pending', 'submitted', 'failed'])
            ->whereNotNull('billing_due_date')
            ->with('products.product:id,name')
            ->get()
            ->map(function (Transaction $transaction) {
                $month = Carbon::parse($transaction->billing_due_date);
                $productName = $transaction->products->first()?->product?->name ?? 'Produk';

                return [
                    'month_key' => $month->format('Y-m'),
                    'month_label' => $month->translatedFormat('F Y'),
                    'description' => sprintf('Tagihan penuh %s', $productName),
                    'amount' => (float) $transaction->total_amount,
                    'status' => $transaction->billing_status,
                    'reference' => $transaction->code,
                ];
            });

        $groups = $installmentPayments
            ->concat($fullBills)
            ->groupBy('month_key')
            ->sortKeys()
            ->map(function ($items) {
                return [
                    'month_label' => $items->first()['month_label'],
                    'items' => $items->values()->all(),
                ];
            })
            ->values();

        return [
            'next_month' => $groups->first(),
            'upcoming' => $groups->slice(1)->values()->all(),
        ];
    }
}
