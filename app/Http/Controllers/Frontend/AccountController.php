<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\CustomerAddress;
use App\Models\Province;
use App\Models\District;
use App\Models\SubDistrict;
use App\Models\Transaction;
use App\Settings\GeneralSettings;
use App\Services\RegionalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    protected $regionalService;

    public function __construct(RegionalService $regionalService)
    {
        $this->regionalService = $regionalService;
    }

    public function __invoke(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $customer->load(['address.province', 'address.district', 'address.subDistrict', 'address.village']);
        $balanceEnabled = app(GeneralSettings::class)->balance_enabled;

        $ordersQuery = Transaction::query()
            ->with([
                'products' => function ($query) {
                    $query->select('id', 'transaction_id', 'product_id', 'quantity', 'price', 'discount', 'description');
                },
                'products.product' => function ($query) {
                    $query->select('id', 'uuid', 'name', 'slug');
                },
                'products.product.media',
            ])
            ->where('customer_id', Auth::guard('customer')->id())
            ->orderBy('created_at', 'desc');

        $totalOrders = (clone $ordersQuery)->count('id');
        $recentOrders = (clone $ordersQuery)
            ->limit(5)
            ->get(['id', 'uuid', 'code', 'customer_id', 'status', 'shipping_cost', 'cod_fee', 'created_at', 'timelimit']);

        return Inertia::render('Account/Profile', [
            'user' => CustomerResource::make($customer),
            'addresses' => AddressResource::collection($customer->address),
            'provinces' => $this->regionalService->getProvinces()->map(fn($p) => ['id' => $p->id, 'name' => $p->name]),
            'totalOrders' => $totalOrders,
            'recentOrders' => OrderResource::collection($recentOrders),
            'balanceEnabled' => $balanceEnabled,
            'balanceHistory' => $balanceEnabled
                ? $customer->balances()->latest()->limit(5)->get(['id', 'amount', 'post_balance', 'trx_type', 'type', 'notes', 'created_at'])
                : [],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'image' => 'nullable|image|max:2048', // 2MB max
        ]);

        $customer->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        if ($request->hasFile('image')) {
            $customer->clearMediaCollection('profile_photos');
            $media = $customer->addMediaFromRequest('image')
                ->toMediaCollection('profile_photos');

            $customer->update([
                'image' => $media->getUrl('thumb')
            ]);
        }

        return back()->with('success', __('messages.success.profile_updated'));
    }

    public function storeAddress(Request $request)
    {
        abort(403);
    }

    public function updateAddress(Request $request, CustomerAddress $address)
    {
        abort(403);
    }

    public function deleteAddress(CustomerAddress $address)
    {
        abort(403);
    }

    public function getDistricts(Province $province)
    {
        return response()->json($this->regionalService->getDistricts($province->id)->map(fn($d) => ['id' => $d->id, 'name' => $d->name]));
    }

    public function getSubDistricts(District $district)
    {
        return response()->json($this->regionalService->getSubdistricts($district->id)->map(fn($s) => ['id' => $s->id, 'name' => $s->name]));
    }

    public function getVillages(SubDistrict $subDistrict)
    {
        return response()->json($this->regionalService->getVillages($subDistrict->id)->map(fn($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'postal_code' => $v->postal_code,
        ]));
    }
}
