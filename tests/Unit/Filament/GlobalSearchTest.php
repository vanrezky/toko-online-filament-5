<?php

namespace Tests\Unit\Filament;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    public function test_product_global_search_configuration_and_details(): void
    {
        $category = new Category(['name' => 'Outdoor']);
        $product = new Product([
            'name' => 'Rustic Umbrella',
            'code' => 'UMBRELLA-001',
        ]);
        $product->setRelation('category', $category);

        $this->assertSame('name', ProductResource::getRecordTitleAttribute());
        $this->assertSame(
            ['name', 'code', 'slug', 'category.name'],
            ProductResource::getGloballySearchableAttributes(),
        );
        $this->assertSame([
            'Kode Produk' => 'UMBRELLA-001',
            'Kategori Produk' => 'Outdoor',
        ], ProductResource::getGlobalSearchResultDetails($product));
    }

    public function test_customer_global_search_configuration_and_details(): void
    {
        $customer = new Customer([
            'first_name' => 'Maryam',
            'last_name' => 'Jakubowski',
            'email' => 'maryam@example.org',
            'phone' => '08123456789',
        ]);

        $this->assertSame('first_name', CustomerResource::getRecordTitleAttribute());
        $this->assertSame(
            ['first_name', 'last_name', 'email', 'username', 'phone'],
            CustomerResource::getGloballySearchableAttributes(),
        );
        $this->assertSame('Maryam Jakubowski', CustomerResource::getGlobalSearchResultTitle($customer));
        $this->assertSame([
            'Email' => 'maryam@example.org',
            'Telepon' => '08123456789',
        ], CustomerResource::getGlobalSearchResultDetails($customer));
    }
}
