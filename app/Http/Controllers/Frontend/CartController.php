<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\CartStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartRecommendationService;
use App\Services\FlashsalePricingService;
use App\Services\ProductInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CartController extends Controller
{
    public function __construct(
        private readonly CartRecommendationService $recommendationService,
        private readonly ProductInventoryService $inventoryService,
    ) {}

    public function __invoke(Request $request)
    {
        $cart = null;
        $recommendations = ProductSimpleResource::collection(collect());
        if (Auth::guard('customer')->check()) {
            $resellerId = Auth::guard('customer')->user()?->reseller_id;

            $cart = Cart::with([
                'items.product.media',
                'items.product.flashsaleProducts' => fn ($query) => $query
                    ->whereHas('flashsale', fn ($query) => $query->current())
                    ->select(['id', 'product_id', 'discount_percentage', 'stock']),
                'items.product.wholesales',
                'items.productVariant.variantAttributes.productAttribute',
                'items.productVariant.variantAttributes.productAttributeOption',
            ])
                ->when($resellerId, fn ($query) => $query->with([
                    'items.product.resellerPrices' => fn ($query) => $query
                        ->where('reseller_id', $resellerId)
                        ->select(['id', 'product_id', 'reseller_id', 'price']),
                ]))
                ->active()
                ->where('customer_id', Auth::guard('customer')->id())
                ->first();

            if ($cart) {
                app(FlashsalePricingService::class)->syncCart($cart);
                $recommendationCart = $cart;
                $recommendationService = $this->recommendationService;
                $recommendations = Inertia::defer(
                    fn () => ProductSimpleResource::collection(
                        $recommendationService->forCart($recommendationCart, $resellerId),
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

    public function store(Request $request)
    {
        if (! Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => __('messages.error.login_required_to_add_cart'),
                    'redirect' => route('frontend.login'),
                ], 401);
            }
            session()->put('url.intended', url()->previous());

            return redirect()->route('frontend.login')->with('error', __('messages.error.login_required_to_add_cart'));
        }

        $request->validate([
            'product_id' => 'required|exists:products,uuid',
            'product_variant_id' => 'nullable|exists:product_variants,uuid',
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItemId = null;
        $cartCount = 0;

        DB::transaction(function () use ($request, &$cartItemId, &$cartCount): void {
            $product = Product::withCount('productVariants')
                ->where('uuid', $request->product_id)
                ->lockForUpdate()
                ->firstOrFail();
            $variant = $request->product_variant_id
                ? ProductVariant::query()
                    ->where('uuid', $request->product_variant_id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first()
                : null;

            if ($request->product_variant_id && ! $variant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => [__('messages.error.invalid_product_variant')],
                ]);
            }

            $cart = Cart::query()
                ->where('customer_id', Auth::guard('customer')->id())
                ->where('status', CartStatus::Active)
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                $cart = Cart::create([
                    'customer_id' => Auth::guard('customer')->id(),
                    'status' => CartStatus::Active,
                ]);
            }

            $item = $cart->items()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant?->id)
                ->lockForUpdate()
                ->first();
            $newQuantity = ($item?->quantity ?? 0) + (int) $request->quantity;

            $this->inventoryService->assertAvailable($product, $variant, $newQuantity);

            $priceInfo = $product->calculatePrice($newQuantity, $variant);

            if ($item) {
                $item->update([
                    'quantity' => $newQuantity,
                    'price' => $priceInfo['price'],
                    'discount' => $priceInfo['discount'],
                ]);
            } else {
                $item = $cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $newQuantity,
                    'price' => $priceInfo['price'],
                    'discount' => $priceInfo['discount'],
                ]);
            }

            $cartItemId = $item->uuid;
            $cartCount = (int) $cart->items()->sum('quantity');
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.success.item_added_to_cart'),
                'cart_count' => $cartCount,
                'cart_item_id' => $cartItemId,
            ]);
        }

        return redirect()->back()->with('success', __('messages.success.item_added_to_bag'));
    }

    public function update(Request $request, CartItem $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        abort_unless(
            $item->cart()->where('customer_id', Auth::guard('customer')->id())->where('status', CartStatus::Active)->exists(),
            404,
        );

        DB::transaction(function () use ($request, $item): void {
            $lockedItem = CartItem::query()
                ->with(['product' => fn ($query) => $query->withCount('productVariants'), 'productVariant'])
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();
            $product = Product::query()
                ->withCount('productVariants')
                ->lockForUpdate()
                ->findOrFail($lockedItem->product_id);
            $variant = $lockedItem->product_variant_id
                ? ProductVariant::query()->lockForUpdate()->find($lockedItem->product_variant_id)
                : null;

            $this->inventoryService->assertAvailable($product, $variant, (int) $request->quantity);
            $priceInfo = $product->calculatePrice((int) $request->quantity, $variant);

            $lockedItem->update([
                'quantity' => $request->quantity,
                'price' => $priceInfo['price'],
                'discount' => $priceInfo['discount'],
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('messages.success.cart_updated')]);
        }

        return redirect()->back()->with('success', __('messages.success.cart_updated'));
    }

    public function destroy(Request $request, CartItem $item)
    {
        $item->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('messages.success.item_removed')]);
        }

        return redirect()->back()->with('success', __('messages.success.item_removed_from_bag'));
    }
}
