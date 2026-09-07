<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_catalog_by_variant_size_without_duplicate_products(): void
    {
        [$size, $medium, $large] = $this->createVariantAttribute('Ukuran', ['M', 'L']);
        $product = Product::factory()->create(['name' => 'Medium Shirt']);
        $this->attachVariant($product, $size, $medium);
        $this->attachVariant($product, $size, $large);
        $otherProduct = Product::factory()->create(['name' => 'Small Shirt']);
        $this->attachVariant($otherProduct, $size, $large);

        $response = $this->get(route('frontend.products', ['variant_size' => 'M']));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('products.data', fn ($products) => collect($products)->pluck('id')->all() === [$product->uuid])
            ->where('filters.variant_size', ['M'])
        );
    }

    public function test_it_requires_each_selected_variant_group_and_accepts_multiple_values(): void
    {
        [$size, $medium] = $this->createVariantAttribute('Ukuran', ['M']);
        [$gender, $women] = $this->createVariantAttribute('Gender', ['Wanita']);
        $matching = Product::factory()->create(['name' => 'Women Medium Shirt']);
        $this->attachVariant($matching, $size, $medium);
        $this->attachVariant($matching, $gender, $women);

        $sizeOnly = Product::factory()->create(['name' => 'Medium Unisex Shirt']);
        $this->attachVariant($sizeOnly, $size, $medium);

        $response = $this->get(route('frontend.products', [
            'variant_size' => ['M', 'L'],
            'variant_gender' => 'Wanita',
        ]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('products.data', fn ($products) => collect($products)->pluck('id')->all() === [$matching->uuid])
            ->where('filters.variant_size', ['M', 'L'])
            ->where('filters.variant_gender', ['Wanita'])
        );
    }

    public function test_it_filters_catalog_by_color_and_gender_variant_groups(): void
    {
        [$color, $black, $red] = $this->createVariantAttribute('Warna', ['Hitam', 'Merah']);
        [$gender, $women, $men] = $this->createVariantAttribute('Gender', ['Wanita', 'Pria']);

        $matching = Product::factory()->create(['name' => 'Black Women Shoes']);
        $this->attachVariant($matching, $color, $black);
        $this->attachVariant($matching, $gender, $women);

        $wrongColor = Product::factory()->create(['name' => 'Red Women Shoes']);
        $this->attachVariant($wrongColor, $color, $red);
        $this->attachVariant($wrongColor, $gender, $women);

        $wrongGender = Product::factory()->create(['name' => 'Black Men Shoes']);
        $this->attachVariant($wrongGender, $color, $black);
        $this->attachVariant($wrongGender, $gender, $men);

        $response = $this->get(route('frontend.products', [
            'variant_color' => 'Hitam',
            'variant_gender' => 'Wanita',
        ]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('products.data', fn ($products) => collect($products)->pluck('id')->all() === [$matching->uuid])
            ->where('filters.variant_color', ['Hitam'])
            ->where('filters.variant_gender', ['Wanita'])
        );
    }

    public function test_it_applies_rating_and_discount_filters(): void
    {
        $matching = Product::factory()->create([
            'price' => 100000,
            'sale_price' => 75000,
            'stock' => 10,
        ]);
        ProductReview::create(['product_id' => $matching->id, 'rating' => 5, 'review' => 'Great']);

        $response = $this->get(route('frontend.products', [
            'rating_min' => 4,
            'promo' => 'discount',
        ]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('products.data', fn ($products) => collect($products)->pluck('id')->all() === [$matching->uuid])
        );
    }

    public function test_it_preserves_variant_filters_in_pagination_links(): void
    {
        [$size, $medium] = $this->createVariantAttribute('Ukuran', ['M']);

        Product::factory()->count(13)->create()->each(fn (Product $product) => $this->attachVariant($product, $size, $medium));

        $response = $this->get(route('frontend.products', ['variant_size' => 'M', 'per_page' => 12]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('products.meta.per_page', 12)
            ->where('products.meta.current_page', 1)
            ->where('products.links.next', function (?string $next): bool {
                parse_str((string) parse_url($next, PHP_URL_QUERY), $query);

            return ($query['variant_size'] ?? null) === 'M';
            })
        );
    }

    public function test_it_normalizes_invalid_sort_and_page_size_and_duplicate_variant_values(): void
    {
        [$size, $medium] = $this->createVariantAttribute('Ukuran', ['M']);
        $product = Product::factory()->create();
        $this->attachVariant($product, $size, $medium);

        $response = $this->get(route('frontend.products', [
            'variant_size' => ['M', 'M', ''],
            'variant_brand' => 'Merek',
            'sort' => 'unsupported',
            'per_page' => 99,
        ]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('filters.variant_size', ['M'])
            ->where('products.meta.per_page', 12)
            ->where('products.links.next', function (?string $next): bool {
                return ! str_contains((string) $next, 'variant_brand');
            })
        );
    }

    private function createVariantAttribute(string $name, array $options): array
    {
        $attribute = ProductAttribute::create([
            'name' => $name,
            'is_global' => true,
            'status' => 'approved',
        ]);

        $createdOptions = collect($options)->map(fn (string $option) => ProductAttributeOption::create([
            'product_attribute_id' => $attribute->id,
            'name' => $option,
            'is_global' => true,
            'status' => 'approved',
        ]));

        return [$attribute, ...$createdOptions->values()->all()];
    }

    private function attachVariant(Product $product, ProductAttribute $attribute, ProductAttributeOption $option): ProductVariant
    {
        $variant = ProductVariant::create([
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'sku' => strtoupper(Str::random(10)),
            'price' => $product->price,
            'stock' => 10,
            'status' => true,
        ]);

        ProductVariantAttribute::create([
            'product_variant_id' => $variant->id,
            'product_attribute_id' => $attribute->id,
            'product_attribute_option_id' => $option->id,
        ]);

        return $variant;
    }
}
