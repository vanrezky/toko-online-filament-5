<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\FrontendVoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class VoucherController extends Controller
{
    public function __construct(private readonly FrontendVoucherService $voucherService) {}

    public function index(Request $request): Response
    {
        $type = $request->filled('type') ? (string) $request->input('type') : null;
        $pendingVouchers = $this->voucherService->pendingVouchers();

        return Inertia::render('Voucher/Index', [
            'vouchers' => $this->voucherService->publicVouchers($type)->map(
                fn ($voucher): array => $this->voucherService->formatVoucher($voucher),
            ),
            'pendingShippingVoucher' => $pendingVouchers['shipping'],
            'pendingProductVoucher' => $pendingVouchers['product'],
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'redirect' => 'nullable|string',
        ]);
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer) {
            $redirect = (string) ($validated['redirect'] ?? route('frontend.checkout'));

            return redirect()->route('frontend.login', [
                'redirect' => $redirect,
                'voucher' => (string) $validated['code'],
            ]);
        }

        $cart = $this->voucherService->activeCart($customer);
        if ($cart === null) {
            return redirect()->back()->with('error', __('messages.error.cart_not_found'));
        }

        $result = $this->voucherService->apply($customer, (string) $validated['code']);
        if ($result['error'] !== null || $result['voucher'] === null || $result['cookie'] === null) {
            return redirect()->back()->with('error', $result['error'] ?? __('messages.error.voucher_invalid_code'));
        }

        $redirect = (string) ($validated['redirect'] ?? route('frontend.checkout'));

        return redirect($redirect)
            ->with('success', __('messages.success.voucher_applied', ['code' => $result['voucher']->code]));
    }

    public function remove(Request $request): RedirectResponse
    {
        $validated = $request->validate(['type' => 'required|in:shipping,product']);
        $result = $this->voucherService->remove((string) $validated['type']);

        return redirect()->back()
            ->with('success', __('messages.success.voucher_removed'));
    }
}
