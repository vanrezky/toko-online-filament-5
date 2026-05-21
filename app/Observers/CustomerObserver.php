<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\SchoolUnitAddressSyncService;

class CustomerObserver
{
    public function __construct(protected SchoolUnitAddressSyncService $syncService)
    {
    }

    public function created(Customer $customer): void
    {
        $this->syncService->syncCustomer($customer);
    }

    public function updated(Customer $customer): void
    {
        if (! $customer->wasChanged(['school_unit_id', 'first_name', 'last_name', 'phone'])) {
            return;
        }

        $this->syncService->syncCustomer($customer);
    }
}
