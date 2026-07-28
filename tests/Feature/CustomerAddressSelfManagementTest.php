<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressSelfManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_store_customer_can_create_and_manage_their_own_address(): void
    {
        $this->setStoreMode(false);
        [$province, $district, $subDistrict, $village] = $this->createRegion();
        $customer = $this->createCustomer('customer@example.test');

        $this->actingAs($customer, 'customer')
            ->post(route('frontend.account.address.store'), $this->addressData($province, $district, $subDistrict, $village))
            ->assertRedirect();

        $address = CustomerAddress::query()->where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame('customer', $address->source_type);
        $this->assertTrue((bool) $address->is_featured);

        $this->actingAs($customer, 'customer')
            ->patch(route('frontend.account.address.update', $address), [
                ...$this->addressData($province, $district, $subDistrict, $village),
                'address' => 'Jl. Customer Baru No. 2',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $address->id,
            'address' => 'Jl. Customer Baru No. 2',
            'source_type' => 'customer',
        ]);

        $this->actingAs($customer, 'customer')
            ->delete(route('frontend.account.address.delete', $address))
            ->assertRedirect();

        $this->assertSoftDeleted('customer_addresses', ['id' => $address->id]);
    }

    public function test_customer_default_selection_supersedes_other_customer_addresses(): void
    {
        $this->setStoreMode(false);
        [$province, $district, $subDistrict, $village] = $this->createRegion();
        $customer = $this->createCustomer('default@example.test');

        $firstAddress = CustomerAddress::create([
            ...$this->addressData($province, $district, $subDistrict, $village),
            'customer_id' => $customer->id,
            'source_type' => 'customer',
            'is_featured' => true,
        ]);
        $secondAddress = CustomerAddress::create([
            ...$this->addressData($province, $district, $subDistrict, $village),
            'customer_id' => $customer->id,
            'name' => 'Alamat Kedua',
            'source_type' => 'customer',
            'is_featured' => false,
        ]);

        $this->actingAs($customer, 'customer')
            ->patch(route('frontend.account.address.update', $secondAddress), [
                ...$this->addressData($province, $district, $subDistrict, $village),
                'name' => 'Alamat Kedua',
                'is_featured' => true,
            ])
            ->assertRedirect();

        $this->assertFalse((bool) $firstAddress->fresh()->is_featured);
        $this->assertTrue((bool) $secondAddress->fresh()->is_featured);
    }

    public function test_private_store_rejects_all_customer_address_mutations(): void
    {
        $this->setStoreMode(true);
        [$province, $district, $subDistrict, $village] = $this->createRegion();
        $customer = $this->createCustomer('private@example.test');
        $address = CustomerAddress::create([
            ...$this->addressData($province, $district, $subDistrict, $village),
            'customer_id' => $customer->id,
            'source_type' => 'customer',
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('frontend.account.address.store'), $this->addressData($province, $district, $subDistrict, $village))
            ->assertForbidden();
        $this->actingAs($customer, 'customer')
            ->patch(route('frontend.account.address.update', $address), $this->addressData($province, $district, $subDistrict, $village))
            ->assertForbidden();
        $this->actingAs($customer, 'customer')
            ->delete(route('frontend.account.address.delete', $address))
            ->assertForbidden();
    }

    public function test_customer_cannot_mutate_another_customer_or_admin_managed_address(): void
    {
        $this->setStoreMode(false);
        [$province, $district, $subDistrict, $village] = $this->createRegion();
        $customer = $this->createCustomer('owner@example.test');
        $otherCustomer = $this->createCustomer('other@example.test');
        $otherAddress = CustomerAddress::create([
            ...$this->addressData($province, $district, $subDistrict, $village),
            'customer_id' => $otherCustomer->id,
            'source_type' => 'customer',
        ]);
        $adminAddress = CustomerAddress::create([
            ...$this->addressData($province, $district, $subDistrict, $village),
            'customer_id' => $customer->id,
            'source_type' => 'manual',
        ]);

        $this->actingAs($customer, 'customer')
            ->patch(route('frontend.account.address.update', $otherAddress), $this->addressData($province, $district, $subDistrict, $village))
            ->assertForbidden();
        $this->actingAs($customer, 'customer')
            ->delete(route('frontend.account.address.delete', $adminAddress))
            ->assertForbidden();
    }

    private function setStoreMode(bool $isPrivateStore): void
    {
        $settings = app(GeneralSettings::class);
        $settings->is_private_store = $isPrivateStore;
        $settings->save();
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'first_name' => 'Customer',
            'last_name' => 'Test',
            'email' => $email,
            'password' => 'Password123!',
            'is_active' => true,
        ]);
    }

    private function addressData(Province $province, District $district, SubDistrict $subDistrict, Village $village): array
    {
        return [
            'name' => 'Customer Test',
            'phone' => '081234567890',
            'province_id' => $province->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'village_id' => $village->id,
            'address' => 'Jl. Customer No. 1',
            'postal_code' => '40111',
            'is_featured' => true,
        ];
    }

    private function createRegion(): array
    {
        $country = Country::create([
            'iso' => 'ID',
            'iso3' => 'IDN',
            'name' => 'Indonesia',
        ]);
        $province = Province::create([
            'country_id' => $country->id,
            'name' => 'Jawa Barat',
            'rajaongkir' => '10',
        ]);
        $district = District::create([
            'province_id' => $province->id,
            'type' => 'city',
            'name' => 'Bandung',
            'rajaongkir' => '501',
            'postal_code' => '40111',
        ]);
        $subDistrict = SubDistrict::create([
            'district_id' => $district->id,
            'name' => 'Coblong',
            'rajaongkir' => '50101',
            'postal_code' => '40111',
        ]);
        $village = Village::create([
            'sub_district_id' => $subDistrict->id,
            'name' => 'Dago',
            'postal_code' => '40111',
            'rajaongkir' => '5010101',
            'apicoid_code' => 'APICD1',
        ]);

        return [$province, $district, $subDistrict, $village];
    }
}
