<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\SchoolUnit;

class SchoolUnitAddressSyncService
{
    public function syncCustomer(Customer $customer): void
    {
        if (! $customer->school_unit_id) {
            return;
        }

        $schoolUnit = $customer->schoolUnit()->first();

        if (! $schoolUnit) {
            return;
        }

        CustomerAddress::where('customer_id', $customer->id)->update(['is_featured' => false]);

        CustomerAddress::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'source_type' => 'school_unit',
            ],
            [
                'name' => $schoolUnit->name,
                'phone' => $customer->phone ?: ($schoolUnit->phone ?: '-'),
                'province_id' => $schoolUnit->province_id,
                'district_id' => $schoolUnit->district_id,
                'sub_district_id' => $schoolUnit->sub_district_id,
                'village_id' => $schoolUnit->village_id,
                'address' => $schoolUnit->address,
                'postal_code' => $schoolUnit->postal_code,
                'is_active' => true,
                'is_featured' => true,
            ]
        );
    }

    public function syncAllCustomersBySchoolUnit(SchoolUnit $schoolUnit): void
    {
        $schoolUnit->customers()->chunkById(100, function ($customers) {
            foreach ($customers as $customer) {
                $this->syncCustomer($customer);
            }
        });
    }
}
