<?php

namespace Tests\Feature;

use App\Models\Flashsale;
use App\Models\Product;
use App\Models\ProductFlashsale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FlashsalePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_flashsale_page_renders_an_empty_state_instead_of_404_when_no_sale_is_active(): void
    {
        $this->get(route('frontend.flashsales'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Flashsale/Index')
                ->where('flashsale', null)
                ->where('products', null)
            );
    }

    public function test_flashsale_page_maps_active_products_through_resources(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $flashsale = Flashsale::query()->create([
            'name' => 'Weekend Drop',
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHour(),
            'is_active' => true,
        ]);
        ProductFlashsale::query()->create([
            'flashsale_id' => $flashsale->id,
            'product_id' => $product->id,
            'discount_percentage' => 25,
            'stock' => 10,
        ]);

        $this->get(route('frontend.flashsales'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Flashsale/Index')
                ->where('flashsale.name', 'Weekend Drop')
                ->where('flashsale.products_count', 1)
                ->has('products.data', 1)
                ->where('products.data.0.discount_percentage', 25)
                ->where('products.data.0.product.slug', $product->slug)
            );
    }
}
