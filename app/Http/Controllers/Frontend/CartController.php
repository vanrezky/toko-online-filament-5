<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Http\Resources\ProductSimpleResource;
use App\Models\CartItem;
use App\Models\Customer;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function __invoke(Request $request): Response
    {
        $customer = Auth::guard('customer')->user();
        $cart = null;
        $recommendations = ProductSimpleResource::collection(collect());

        if ($customer instanceof Customer) {
            $resellerId = $customer->reseller_id;
            $cart = $this->cartService->activeCart($customer);

            if ($cart !== null) {
                $recommendations = Inertia::defer(
                    fn () => ProductSimpleResource::collection(
                        $this->cartService->recommendations($cart, $resellerId),
                    ),
                    'recommendations',
                );
                $cart = CartResource::make($cart)->resolve();
            }
        }

        return Inertia::render('Cart/Index', [
            'cart' => $cart,
            'recommendations' => $recommendations,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => __('messages.error.login_required_to_add_cart'),
                    'redirect' => route('frontend.login'),
                ], 401);
            }

            session()->put('url.intended', url()->previous());

            return redirect()->route('frontend.login')->with('error', __('messages.error.login_required_to_add_cart'));
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,uuid',
            'product_variant_id' => 'nullable|exists:product_variants,uuid',
            'quantity' => 'required|integer|min:1',
        ]);
        $result = $this->cartService->addItem(
            $customer,
            (string) $validated['product_id'],
            isset($validated['product_variant_id']) ? (string) $validated['product_variant_id'] : null,
            (int) $validated['quantity'],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.success.item_added_to_cart'),
                'cart_count' => $result['cart_count'],
                'cart_item_id' => $result['cart_item_id'],
            ]);
        }

        return redirect()->back()->with('success', __('messages.success.item_added_to_bag'));
    }

    public function update(Request $request, CartItem $item): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:1']);
        $customer = $this->customer();
        $this->cartService->updateItem($customer, $item, (int) $validated['quantity']);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('messages.success.cart_updated')]);
        }

        return redirect()->back()->with('success', __('messages.success.cart_updated'));
    }

    public function destroy(Request $request, CartItem $item): JsonResponse|RedirectResponse
    {
        $this->cartService->removeItem($this->customer(), $item);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('messages.success.item_removed')]);
        }

        return redirect()->back()->with('success', __('messages.success.item_removed_from_bag'));
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
