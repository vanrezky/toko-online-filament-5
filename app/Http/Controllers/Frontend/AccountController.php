<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Services\AccountProfileService;
use App\Services\RegionalService;
use App\Settings\GeneralSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountProfileService $profileService,
        private readonly RegionalService $regionalService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $customer = $this->customer();
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

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = $this->customer();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email,'.$customer->id,
            'phone' => 'nullable|string|max:20',
            'image' => 'nullable|image|max:2048', // 2MB max
        ]);

        $this->profileService->updateProfile($customer, [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->filled('phone') ? (string) $request->input('phone') : null,
        ], $request->file('image'));

        return back()->with('success', __('messages.success.profile_updated'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = $this->customer();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', securePassword(8)],
        ]);

        $this->profileService->updatePassword($customer, (string) $validated['password']);

        return back()->with('success', __('messages.success.password_updated'));
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $this->ensurePublicStore();

        $customer = $this->customer();
        $validated = $this->validatedAddress($request);
        $this->profileService->createAddress($customer, $validated);

        return back()->with('success', __('messages.success.address_added'));
    }

    public function updateAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->ensureCustomerCanManageAddress($address);

        $validated = $this->validatedAddress($request);
        $this->profileService->updateAddress($address, $validated);

        return back()->with('success', __('messages.success.address_updated'));
    }

    public function deleteAddress(CustomerAddress $address): RedirectResponse
    {
        $this->ensureCustomerCanManageAddress($address);

        $this->profileService->deleteAddress($address);

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

    /** @return array{name: string, phone: string, province_id: int, district_id: int, sub_district_id: int, village_id: int, address: string, postal_code: string, is_featured?: bool} */
    private function validatedAddress(Request $request): array
    {
        $validated = $request->validate([
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

        $address = [
            'name' => (string) $validated['name'],
            'phone' => (string) $validated['phone'],
            'province_id' => (int) $validated['province_id'],
            'district_id' => (int) $validated['district_id'],
            'sub_district_id' => (int) $validated['sub_district_id'],
            'village_id' => (int) $validated['village_id'],
            'address' => (string) $validated['address'],
            'postal_code' => (string) $validated['postal_code'],
        ];

        if (array_key_exists('is_featured', $validated)) {
            $address['is_featured'] = (bool) $validated['is_featured'];
        }

        return $address;
    }

    public function getDistricts(Province $province): JsonResponse
    {
        return response()->json($this->regionalService->getDistricts($province->id)->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]));
    }

    public function getSubDistricts(District $district): JsonResponse
    {
        return response()->json($this->regionalService->getSubdistricts($district->id)->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]));
    }

    public function getVillages(SubDistrict $subDistrict): JsonResponse
    {
        return response()->json($this->regionalService->getVillages($subDistrict->id)->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'postal_code' => $v->postal_code,
        ]));
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
