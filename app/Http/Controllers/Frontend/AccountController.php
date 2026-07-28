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
        $this->ensurePublicStore();

        $customer = Auth::guard('customer')->user();
        $validated = $this->validatedAddress($request);
        $isFeatured = (bool) ($validated['is_featured'] ?? false);

        if ($isFeatured) {
            $customer->address()->update(['is_featured' => false]);
        }

        $customer->address()->create([
            ...$validated,
            'is_featured' => $isFeatured,
            'source_type' => 'customer',
        ]);

        return back()->with('success', __('messages.success.address_added'));
    }

    public function updateAddress(Request $request, CustomerAddress $address)
    {
        $this->ensureCustomerCanManageAddress($address);

        $validated = $this->validatedAddress($request);
        $isFeatured = (bool) ($validated['is_featured'] ?? false);

        if ($isFeatured) {
            CustomerAddress::where('customer_id', $address->customer_id)->update(['is_featured' => false]);
        } else {
            unset($validated['is_featured']);
        }

        $address->update([
            ...$validated,
            ...($isFeatured ? ['is_featured' => true] : []),
        ]);

        return back()->with('success', __('messages.success.address_updated'));
    }

    public function deleteAddress(CustomerAddress $address)
    {
        $this->ensureCustomerCanManageAddress($address);

        $address->delete();

        return back()->with('success', __('messages.success.address_deleted'));
    }

    private function ensurePublicStore(): void
    {
        abort_if(app(GeneralSettings::class)->is_private_store, 403);
    }

    private function ensureCustomerCanManageAddress(CustomerAddress $address): void
    {
        $this->ensurePublicStore();

        abort_unless(
            $address->customer_id === Auth::guard('customer')->id()
                && $address->source_type === 'customer',
            403,
        );
    }

    private function validatedAddress(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'province_id' => ['required', 'exists:provinces,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'sub_district_id' => ['required', 'exists:sub_districts,id'],
            'village_id' => ['required', 'exists:villages,id'],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
        ]);
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
