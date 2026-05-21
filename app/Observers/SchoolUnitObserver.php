<?php

namespace App\Observers;

use App\Models\SchoolUnit;
use App\Services\SchoolUnitAddressSyncService;

class SchoolUnitObserver
{
    public function __construct(protected SchoolUnitAddressSyncService $syncService)
    {
    }

    public function updated(SchoolUnit $schoolUnit): void
    {
        if (! $schoolUnit->wasChanged([
            'name',
            'phone',
            'province_id',
            'district_id',
            'sub_district_id',
            'village_id',
            'address',
            'postal_code',
        ])) {
            return;
        }

        $this->syncService->syncAllCustomersBySchoolUnit($schoolUnit);
    }
}
