<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerLevels\CustomerLevelResource;
use App\Filament\Resources\Customers\CustomerResource;
use Tests\TestCase;

class CustomerClusterNavigationTest extends TestCase
{
    public function test_customers_are_the_first_navigation_item_in_the_customer_cluster(): void
    {
        $this->assertLessThan(
            CustomerLevelResource::getNavigationSort(),
            CustomerResource::getNavigationSort(),
        );
    }
}
