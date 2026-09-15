<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Customer;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlistService) {}

    public function index(): Response
    {
        $customer = $this->customer();

        return Inertia::render('Wishlist/Index', [
            'products' => ProductSimpleResource::collection(
                $this->wishlistService->products($customer, $customer->reseller_id),
            ),
        ]);
    }

    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'unauthenticated'], 401);
            }

            session()->put('url.intended', url()->previous());

            return redirect()->route('frontend.login');
        }

        $validated = $request->validate(['product_id' => 'required|exists:products,uuid']);
        $result = $this->wishlistService->toggle($customer, (string) $validated['product_id']);

        if ($request->expectsJson()) {
            return response()->json(['status' => $result]);
        }

        return back();
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
