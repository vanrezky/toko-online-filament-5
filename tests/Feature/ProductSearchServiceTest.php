<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use App\Services\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ranks_exact_name_and_code_matches_before_partial_matches(): void
    {
        $exact = Product::factory()->create([
            'name' => 'Running',
            'code' => 'RUNNING-EXACT',
        ]);
        $partial = Product::factory()->create([
            'name' => 'Running Shoes Premium',
            'code' => 'SHOES-001',
        ]);
        $codeMatch = Product::factory()->create([
            'name' => 'Daily Sports Item',
            'code' => 'RUNNING-CODE',
        ]);

        $results = $this->search('Running');

        $this->assertSame([$exact->id, $codeMatch->id, $partial->id], $results->all());
    }

    public function test_it_searches_category_variant_and_description_fields(): void
    {
        $category = Category::factory()->create(['name' => 'Outdoor Equipment']);
        $categoryProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Mountain Product',
        ]);
        $variantProduct = Product::factory()->create([
            'name' => 'Travel Product',
            'variant' => 'Mountain Green',
        ]);
        $descriptionProduct = Product::factory()->create([
            'name' => 'Daily Product',
            'description' => 'Designed for heliotrope mountain adventures.',
        ]);

        // Make the freshly-created InnoDB full-text index visible inside the test transaction.
        DB::statement('OPTIMIZE TABLE products');
        $this->assertContains($categoryProduct->id, $this->search('Outdoor')->all());
        $this->assertContains($variantProduct->id, $this->search('Mountain')->all());
        $this->assertContains($descriptionProduct->id, $this->search('heliotrope')->all());
    }

    public function test_it_searches_product_attribute_options_without_duplicate_products(): void
    {
        $product = Product::factory()->create(['name' => 'Classic Shirt']);
        $attribute = ProductAttribute::create([
            'name' => 'Brand',
            'product_id' => $product->id,
            'status' => 'approved',
            'is_global' => false,
            'user_id' => $product->user_id,
        ]);
        ProductAttributeOption::create([
            'product_attribute_id' => $attribute->id,
            'name' => 'Northwind',
            'product_id' => $product->id,
            'status' => 'approved',
            'is_global' => false,
            'user_id' => $product->user_id,
        ]);

        $results = $this->search('Northwind');

        $this->assertSame([$product->id], $results->all());
    }

    public function test_it_preserves_active_filter_and_does_not_search_blank_terms(): void
    {
        $active = Product::factory()->create(['name' => 'Visible Product', 'is_active' => true]);
        Product::factory()->create(['name' => 'Hidden Product', 'is_active' => false]);

        $query = Product::query()->select('products.*')->active();
        app(ProductSearchService::class)->apply($query, '   ');

        $this->assertSame([$active->id], $query->pluck('products.id')->all());
        $this->assertFalse(str_contains($query->toSql(), 'search_relevance'));
    }

    public function test_its_query_can_be_explained_without_a_leading_wildcard_name_predicate(): void
    {
        $query = Product::query()->select('products.*')->active();
        app(ProductSearchService::class)->apply($query, 'running');

        $plan = DB::select('EXPLAIN '.$query->toSql(), $query->getBindings());

        $this->assertNotEmpty($plan);
        $this->assertStringContainsString('products.name like ?', strtolower($query->toSql()));
        $this->assertStringNotContainsString("like '%", strtolower($query->toRawSql()));
    }

    public function test_catalog_search_preserves_category_and_price_filters(): void
    {
        $category = Category::factory()->create(['name' => 'Search Category']);
        Product::factory()->create([
            'name' => 'Searchable Product',
            'category_id' => $category->id,
            'price' => 100000,
        ]);
        Product::factory()->create([
            'name' => 'Searchable Product Outside Category',
            'price' => 100000,
        ]);
        Product::factory()->create([
            'name' => 'Searchable Product Outside Price',
            'category_id' => $category->id,
            'price' => 10000,
        ]);

        $response = $this->get(route('frontend.products', [
            'search' => 'Searchable',
            'category' => $category->slug,
            'price_min' => 50000,
        ]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Products/Index')
            ->where('filters.search', 'Searchable')
            ->where('filters.category', $category->slug)
            ->where('filters.price_min', '50000')
            ->has('products.data', 1)
        );
    }

    public function test_catalog_pagination_retains_the_search_parameter(): void
    {
        Product::factory()->count(13)->create(['name' => 'Paged Product']);

        $response = $this->get(route('frontend.products', ['search' => 'Paged']));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('products.meta.current_page', 1)
            ->where('products.meta.per_page', 12)
            ->where('products.links.next', fn (string $next): bool => str_contains($next, 'search=Paged'))
        );
    }

    public function test_existing_sort_is_used_as_secondary_ordering_and_empty_search_is_empty(): void
    {
        $cheap = Product::factory()->create(['name' => 'Sortable Product', 'price' => 10000]);
        Product::factory()->create(['name' => 'Sortable Product', 'price' => 20000]);

        $query = Product::query()->select('products.*')->active();
        app(ProductSearchService::class)->apply($query, 'Sortable');

        $this->assertSame($cheap->id, $query->orderBy('price')->value('products.id'));
        $this->assertCount(0, $this->search('term-that-does-not-exist'));
    }

    private function search(string $term)
    {
        $query = Product::query()->select('products.*')->active();

        return app(ProductSearchService::class)->apply($query, $term)->pluck('products.id');
    }
}
