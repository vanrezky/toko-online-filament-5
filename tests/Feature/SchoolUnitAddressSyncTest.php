<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\District;
use App\Models\Province;
use App\Models\SchoolUnit;
use App\Models\SubDistrict;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchoolUnitAddressSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_address_is_created_from_school_unit(): void
    {
        [$province, $district, $subDistrict, $village] = $this->createRegion();

        $schoolUnit = SchoolUnit::create([
            'name' => 'SDN Contoh',
            'phone' => '081234567890',
            'province_id' => $province->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'village_id' => $village->id,
            'address' => 'Jl. Sekolah No. 1',
            'postal_code' => '40111',
        ]);

        $customer = Customer::create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
            'phone' => '0899999999',
            'is_active' => true,
            'school_unit_id' => $schoolUnit->id,
        ]);

        $address = CustomerAddress::where('customer_id', $customer->id)->where('source_type', 'school_unit')->first();

        $this->assertNotNull($address);
        $this->assertSame('Jl. Sekolah No. 1', $address->address);
        $this->assertTrue((bool) $address->is_featured);
    }

    public function test_updating_school_unit_syncs_related_customer_addresses(): void
    {
        [$province, $district, $subDistrict, $village] = $this->createRegion();
        $schoolUnit = SchoolUnit::create([
            'name' => 'SMP A',
            'phone' => '081234567890',
            'province_id' => $province->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'village_id' => $village->id,
            'address' => 'Alamat Lama',
            'postal_code' => '40111',
        ]);

        $customer = Customer::create([
            'first_name' => 'Siti',
            'last_name' => 'Aminah',
            'email' => 'siti@example.com',
            'password' => Hash::make('password123'),
            'phone' => '0811111111',
            'is_active' => true,
            'school_unit_id' => $schoolUnit->id,
        ]);

        $schoolUnit->update([
            'address' => 'Alamat Baru Unit Sekolah',
            'postal_code' => '40222',
        ]);

        $address = CustomerAddress::where('customer_id', $customer->id)->where('source_type', 'school_unit')->firstOrFail();
        $this->assertSame('Alamat Baru Unit Sekolah', $address->address);
        $this->assertSame('40222', $address->postal_code);
    }

    public function test_customer_cannot_modify_address_from_frontend(): void
    {
        [$province, $district, $subDistrict, $village] = $this->createRegion();
        $schoolUnit = SchoolUnit::create([
            'name' => 'SMA A',
            'phone' => '081234567890',
            'province_id' => $province->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'village_id' => $village->id,
            'address' => 'Alamat Unit',
            'postal_code' => '40111',
        ]);

        $customer = Customer::create([
            'first_name' => 'Dina',
            'last_name' => 'Putri',
            'email' => 'dina@example.com',
            'password' => Hash::make('password123'),
            'phone' => '0812222222',
            'is_active' => true,
            'school_unit_id' => $schoolUnit->id,
        ]);

        $address = CustomerAddress::where('customer_id', $customer->id)->firstOrFail();

        $this->actingAs($customer, 'customer')
            ->patch(route('frontend.account.address.update', $address), [
                'name' => 'Nama Baru',
            ])
            ->assertForbidden();
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
