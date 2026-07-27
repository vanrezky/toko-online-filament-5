<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $wishlistItems = [];

        if ($customer) {
            $resellerId = $customer->reseller_id;

            $wishlistItems = Wishlist::where('customer_id', $customer->id)
                ->with([
                    'product' => fn ($query) => $query->select([
                        'id', 'uuid', 'name', 'slug', 'digital', 'code',
                        'stock', 'sale_price', 'price', 'min_order', 'fake_sold_count',
                    ]),
                    'product.media',
                    'product.flashsaleProducts' => fn ($query) => $query
                        ->whereHas('flashsale', fn ($query) => $query->current())
                        ->select(['id', 'product_id', 'discount_percentage', 'stock']),
                    'product.wholesales' => fn ($query) => $query
                        ->where('min_qty', '<=', 1)
                        ->select(['id', 'product_id', 'min_qty', 'price']),
                ])
                ->when($resellerId, fn ($query) => $query->with([
                    'product.resellerPrices' => fn ($query) => $query
                        ->where('reseller_id', $resellerId)
                        ->select(['id', 'product_id', 'reseller_id', 'price']),
                ]))
                ->get()
                ->pluck('product')
                ->filter()
                ->unique('id');
        }

        return Inertia::render('Wishlist/Index', [
            'products' => ProductSimpleResource::collection($wishlistItems)
        ]);
    }

    public function toggle(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            session()->put('url.intended', url()->previous());
            return redirect()->route('frontend.login');
        }

        $request->validate([
            'product_id' => 'required|exists:products,uuid',
        ]);

        $product = Product::select('id')->where('uuid', $request->product_id)->first();
        $wishlist = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $status = 'removed';
        } else {
            Wishlist::create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
            ]);
            $status = 'added';
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => $status]);
        }

        return back();
    }
}
