<?php

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\CacheService;
use App\Services\CartRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_distinct_cart_categories_and_uses_an_in_stock_fallback(): void
    {
        $primaryCategory = Category::factory()->create();
        $secondaryCategory = Category::factory()->create();
        $fallbackCategory = Category::factory()->create();
        $cart = $this->createCart();

        $this->addCartProduct($cart, $primaryCategory->id, 20);
        $this->addCartProduct($cart, $primaryCategory->id, 1);
        $this->addCartProduct($cart, $secondaryCategory->id, 1);

        $primaryCandidates = Product::factory()->count(2)->create(['category_id' => $primaryCategory->id]);
        $secondaryCandidate = Product::factory()->create(['category_id' => $secondaryCategory->id]);
        $fallbackCandidates = Product::factory()->count(2)->create(['category_id' => $fallbackCategory->id]);
        $inactive = Product::factory()->create(['category_id' => $primaryCategory->id, 'is_active' => false]);
        $outOfStock = Product::factory()->create(['category_id' => $primaryCategory->id, 'stock' => 0]);

        $recommendations = app(CartRecommendationService::class)->forCart(
            $cart->load('items.product'),
        );

        $this->assertCount(4, $recommendations);
        $this->assertSame(
            [$primaryCategory->id, $primaryCategory->id, $secondaryCategory->id],
            $recommendations->take(3)->pluck('category_id')->all(),
        );
        $this->assertEmpty(array_intersect(
            $cart->items->pluck('product_id')->all(),
            $recommendations->pluck('id')->all(),
        ));
        $this->assertFalse($recommendations->contains('id', $inactive->id));
        $this->assertFalse($recommendations->contains('id', $outOfStock->id));
        $candidateIds = $primaryCandidates->concat([$secondaryCandidate])->concat($fallbackCandidates)->pluck('id')->all();
        $this->assertTrue($recommendations->pluck('id')->every(fn ($id): bool => in_array($id, $candidateIds, true)));
    }

    public function test_it_reuses_candidate_ids_but_rechecks_stock_and_invalidates_on_product_save(): void
    {
        $category = Category::factory()->create();
        $cart = $this->createCart();
        $this->addCartProduct($cart, $category->id);
        $candidate = Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->count(5)->create();
        $service = app(CartRecommendationService::class);

        $first = $service->forCart($cart->load('items.product'));
        $cacheKey = "cart-recommendation-category-ids:{$category->id}:v1";
        $cachedIds = CacheService::getManaged('product-catalog', $cacheKey);

        $this->assertContains($candidate->id, $cachedIds);
        $this->assertTrue($first->contains('id', $candidate->id));

        Product::query()->whereKey($candidate->id)->update(['stock' => 0]);

        $second = $service->forCart($cart);

        $this->assertSame($cachedIds, CacheService::getManaged('product-catalog', $cacheKey));
        $this->assertFalse($second->contains('id', $candidate->id));

        $newCandidate = Product::factory()->create(['category_id' => $category->id]);

        $this->assertNull(CacheService::getManaged('product-catalog', $cacheKey));
        $this->assertTrue($service->forCart($cart)->contains('id', $newCandidate->id));
    }

    public function test_cart_page_exposes_real_recommendations_without_cart_products(): void
    {
        $customer = $this->createCustomer();
        $category = Category::factory()->create();
        $cart = $this->createCart($customer);
        $cartProduct = $this->addCartProduct($cart, $category->id);
        $candidate = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($customer, 'customer')
            ->get(route('frontend.cart'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->where('recommendations.0.id', $candidate->uuid)
                ->where('recommendations.0.slug', $candidate->slug)
                ->where('recommendations', fn ($products) => collect($products)
                    ->pluck('id')
                    ->doesntContain($cartProduct->uuid)));
    }

    private function createCustomer(): Customer
    {
        return Customer::query()->create([
            'first_name' => 'Cart',
            'last_name' => 'Customer',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'is_active' => true,
        ]);
    }

    private function createCart(?Customer $customer = null): Cart
    {
        $customer ??= $this->createCustomer();

        return Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
    }

    private function addCartProduct(Cart $cart, int $categoryId, int $quantity = 1): Product
    {
        $product = Product::factory()->create(['category_id' => $categoryId]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->price,
        ]);

        return $product;
    }
}
