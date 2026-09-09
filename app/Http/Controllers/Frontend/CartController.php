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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CartController extends Controller
{
    public function __construct(
        private readonly CartRecommendationService $recommendationService,
    ) {}

    public function __invoke(Request $request)
    {
        $cart = null;
        $recommendations = collect();
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
                $recommendations = $this->recommendationService->forCart($cart, $resellerId);
                $cart = CartResource::make($cart)->resolve();
            }
        }

        return Inertia::render('Cart/Index', [
            'cart' => $cart,
            'recommendations' => ProductSimpleResource::collection($recommendations),
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

        $product = Product::withCount('productVariants')->where('uuid', $request->product_id)->first();
        $price = $product->price;
        $discount = 0;

        if (! $request->product_variant_id && $product->product_variants_count > 0) {
            if ($request->expectsJson()) {
                return response()->json(['error' => __('messages.error.variant_required')], 422);
            }

            return redirect()->back()->with('error', __('messages.error.variant_required'));
        }

        $variant = null;
        if ($request->product_variant_id) {
            $variant = ProductVariant::where('uuid', $request->product_variant_id)->first();
        }

        $priceInfo = $product->calculatePrice($request->quantity, $variant);
        $price = $priceInfo['price'];
        $discount = $priceInfo['discount'];

        $cart = Cart::firstOrCreate(
            ['customer_id' => Auth::guard('customer')->id(), 'status' => CartStatus::Active]
        );

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        if ($item) {
            $newQuantity = $item->quantity + $request->quantity;
            $priceInfo = $product->calculatePrice($newQuantity, $variant);
            $item->update([
                'quantity' => $newQuantity,
                'price' => $priceInfo['price'],
                'discount' => $priceInfo['discount'],
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $request->quantity,
                'price' => $price,
                'discount' => $discount,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.success.item_added_to_cart'),
                'cart_count' => $cart->items()->sum('quantity'),
                'cart_item_id' => $cart->items()
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $variant?->id)
                    ->value('uuid'),
            ]);
        }

        return redirect()->back()->with('success', __('messages.success.item_added_to_bag'));
    }

    public function update(Request $request, CartItem $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $priceInfo = $item->product->calculatePrice($request->quantity, $item->productVariant);

        $item->update([
            'quantity' => $request->quantity,
            'price' => $priceInfo['price'],
            'discount' => $priceInfo['discount'],
        ]);

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
