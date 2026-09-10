<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\CustomerAddress;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Services\AccountProfileService;
use App\Services\RegionalService;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountProfileService $profileService,
        private readonly RegionalService $regionalService,
    ) {
    }

    public function __invoke(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $this->profileService->loadAddresses($customer);
        $balanceEnabled = app(GeneralSettings::class)->balance_enabled;

        return Inertia::render('Account/Profile', [
            'user' => CustomerResource::make($customer),
            'addresses' => AddressResource::collection($customer->address),
            'provinces' => Inertia::defer(fn () => $this->profileService->getProvinces()),
            'totalOrders' => Inertia::defer(fn () => $this->profileService->getTotalOrders($customer)),
            'recentOrders' => Inertia::defer(fn () => OrderResource::collection($this->profileService->getRecentOrders($customer))),
            'balanceEnabled' => $balanceEnabled,
            'balanceHistory' => $balanceEnabled
                ? Inertia::defer(fn () => $this->profileService->getBalanceHistory($customer))
                : [],
            'passwordRequirementsEnabled' => (bool) app(GeneralSettings::class)->secure_password,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email,'.$customer->id,
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
                ->toMediaCollection('profile_photos', config('filesystems.upload_disk'));

            $customer->update([
                'image' => $media->getUrl('thumb'),
            ]);
        }

        return back()->with('success', __('messages.success.profile_updated'));
    }

    public function updatePassword(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', securePassword(8)],
        ]);

        $customer->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', __('messages.success.password_updated'));
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
            'phone' => ['required', 'string', 'max:15', 'regex:/^[0-9]+$/'],
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
        return response()->json($this->regionalService->getDistricts($province->id)->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]));
    }

    public function getSubDistricts(District $district)
    {
        return response()->json($this->regionalService->getSubdistricts($district->id)->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]));
    }

    public function getVillages(SubDistrict $subDistrict)
    {
        return response()->json($this->regionalService->getVillages($subDistrict->id)->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'postal_code' => $v->postal_code,
        ]));
    }
}
