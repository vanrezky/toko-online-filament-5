<?php

namespace Tests\Feature;

use App\Models\Flashsale;
use App\Models\Product;
use App\Models\ProductFlashsale;
use App\Services\FlashsalePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FlashsalePricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_flashsale_price_wins_over_product_sale_price_and_wholesale(): void
    {
        $product = Product::factory()->create([
            'price' => 100_000,
            'sale_price' => 80_000,
        ]);
        $product->wholesales()->create(['min_qty' => 2, 'price' => 70_000]);

        $flashsale = Flashsale::query()->create([
            'name' => 'Flash sale aktif',
            'start_time' => now()->subMinute(),
            'end_time' => now()->addMinute(),
            'is_active' => true,
        ]);
        ProductFlashsale::query()->create([
            'flashsale_id' => $flashsale->id,
            'product_id' => $product->id,
            'discount_percentage' => 30,
            'stock' => 5,
        ]);

        $pricing = app(FlashsalePricingService::class)->resolve($product, null, 2);

        $this->assertSame('flashsale', $pricing['source']);
        $this->assertSame(100_000.0, $pricing['original_price']);
        $this->assertSame(70_000.0, $pricing['price']);
        $this->assertSame(30_000.0, $pricing['discount']);
    }

    public function test_inactive_or_expired_flashsale_does_not_change_regular_price(): void
    {
        $product = Product::factory()->create([
            'price' => 100_000,
            'sale_price' => 80_000,
        ]);
        $flashsale = Flashsale::query()->create([
            'name' => 'Flash sale habis',
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
            'is_active' => true,
        ]);
        ProductFlashsale::query()->create([
            'flashsale_id' => $flashsale->id,
            'product_id' => $product->id,
            'discount_percentage' => 50,
            'stock' => 5,
        ]);

        $pricing = app(FlashsalePricingService::class)->resolve($product, null, 1);

        $this->assertSame('sale', $pricing['source']);
        $this->assertSame(80_000.0, $pricing['price']);
    }

    public function test_flashsale_product_requires_at_least_twenty_five_percent_discount(): void
    {
        $this->expectException(ValidationException::class);

        $product = Product::factory()->create();
        $flashsale = Flashsale::query()->create([
            'name' => 'Flash sale invalid',
            'start_time' => now()->subMinute(),
            'end_time' => now()->addMinute(),
            'is_active' => true,
        ]);

        ProductFlashsale::query()->create([
            'flashsale_id' => $flashsale->id,
            'product_id' => $product->id,
            'discount_percentage' => 24.99,
            'stock' => 1,
        ]);
    }
}
