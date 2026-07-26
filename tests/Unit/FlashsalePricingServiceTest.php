<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductFlashsale;
use App\Models\ProductVariant;
use App\Services\FlashsalePricingService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FlashsalePricingServiceTest extends TestCase
{
    #[Test]
    public function it_calculates_the_minimum_twenty_five_percent_flashsale_discount(): void
    {
        $product = new Product(['price' => 1_000_000]);
        $flashsaleProduct = new ProductFlashsale([
            'discount_percentage' => 25,
            'stock' => 10,
        ]);
        $flashsaleProduct->setAttribute('id', 42);

        $pricing = (new FlashsalePricingService)->resolveFlashsale($product, null, $flashsaleProduct);

        $this->assertSame('flashsale', $pricing['source']);
        $this->assertSame(1_000_000.0, $pricing['original_price']);
        $this->assertSame(750_000.0, $pricing['price']);
        $this->assertSame(250_000.0, $pricing['discount']);
        $this->assertSame(42, $pricing['flashsale_product_id']);
        $this->assertSame(25.0, $pricing['flashsale_discount_percentage']);
    }

    #[Test]
    public function it_applies_the_flashsale_percentage_to_the_selected_variant_price(): void
    {
        $product = new Product(['price' => 1_000_000]);
        $variant = new ProductVariant(['price' => 1_200_000]);
        $flashsaleProduct = new ProductFlashsale([
            'discount_percentage' => 30,
            'stock' => 4,
        ]);
        $flashsaleProduct->setAttribute('id', 7);

        $pricing = (new FlashsalePricingService)->resolveFlashsale($product, $variant, $flashsaleProduct);

        $this->assertSame(1_200_000.0, $pricing['original_price']);
        $this->assertSame(840_000.0, $pricing['price']);
        $this->assertSame(360_000.0, $pricing['discount']);
        $this->assertSame(4, $pricing['flashsale_stock']);
    }
}
