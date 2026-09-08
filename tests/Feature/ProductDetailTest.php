<?php

namespace Tests\Feature;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_original_gallery_urls_and_separate_thumbnail_urls(): void
    {
        $product = Product::factory()->create();

        foreach (range(1, 2) as $number) {
            $product->addMediaFromString($this->png())
                ->usingFileName("detail-{$number}.png")
                ->toMediaCollection();
        }

        $response = (new ProductResource($product->fresh()))->resolve();

        $this->assertCount(2, $response['images']);
        $this->assertCount(2, $response['image_thumbnails']);
        $this->assertSame($product->fresh()->getMedia()->map->getUrl()->all(), $response['images']->all());
        $this->assertNotSame($response['images'][0], $response['image_thumbnails'][0]);
    }

    public function test_it_returns_only_active_same_category_related_products_without_the_current_product(): void
    {
        $product = Product::factory()->create();
        $relatedProducts = Product::factory()->count(7)->create(['category_id' => $product->category_id]);
        Product::factory()->create(['category_id' => $product->category_id, 'is_active' => false]);
        Product::factory()->create();

        $response = $this->get(route('frontend.product-detail', $product->slug));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('relatedProducts', function ($products) use ($product, $relatedProducts): bool {
                $ids = collect($products)->pluck('id');

                return $ids->count() === 6
                    && ! $ids->contains($product->uuid)
                    && $ids->every(fn ($id) => $relatedProducts->pluck('uuid')->contains($id));
            })
        );
    }

    public function test_it_invalidates_cached_related_products_when_a_catalog_product_is_saved(): void
    {
        $product = Product::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $product->category_id]);

        $this->get(route('frontend.product-detail', $product->slug))->assertOk();

        $newProduct = Product::factory()->create(['category_id' => $product->category_id]);

        $this->get(route('frontend.product-detail', $product->slug))
            ->assertInertia(fn ($page) => $page
                ->where('relatedProducts', function ($products) use ($newProduct): bool {
                    return collect($products)->pluck('id')->contains($newProduct->uuid);
                })
            );
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }
}
