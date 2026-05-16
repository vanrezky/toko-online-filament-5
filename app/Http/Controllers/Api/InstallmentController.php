<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstallmentPlan;
use App\Services\CreditLimitService;
use App\Services\InstallmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function __construct(
        protected InstallmentService $installmentService,
        protected CreditLimitService $creditLimitService
    ) {}

    public function plans(): JsonResponse
    {
        $plans = InstallmentPlan::active()->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'plan_id' => 'required|exists:installment_plans,id',
        ]);

        $plan = InstallmentPlan::find($request->plan_id);
        $amount = (float) $request->amount;

        $totalAmount = $plan->calculateTotal($amount);
        $feeAmount = $totalAmount - $amount;
        $monthlyAmount = $plan->calculateMonthly($amount);

        return response()->json([
            'success' => true,
            'data' => [
                'principal_amount' => $amount,
                'fee_amount' => $feeAmount,
                'total_amount' => $totalAmount,
                'monthly_amount' => $monthlyAmount,
                'tenor' => $plan->tenor,
                'fee_percentage' => $plan->fee_percentage,
            ],
        ]);
    }

    public function creditLimit(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'credit_limit' => $customer->effective_credit_limit,
                'outstanding_balance' => $customer->outstanding_balance,
                'remaining_credit_limit' => $customer->remaining_credit_limit,
            ],
        ]);
    }
}