<?php

namespace Tests\Feature\Filament;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create([
            'name' => 'Test Category',
            'is_active' => true,
        ]);
    }

    public function test_product_auto_generates_slug_from_name_on_create(): void
    {
        $productName = 'Test Product Name';

        $product = Product::create([
            'name' => $productName,
            'code' => 'PROD-1234-ABCD',
            'category_id' => $this->category->id,
            'description' => 'Test product description',
            'digital' => true,
            'price' => 100000,
            'stock' => 999,
            'security_stock' => 0,
            'min_order' => 1,
            'user_id' => $this->user->id,
            // Note: slug is not provided, should be auto-generated
        ]);

        $this->assertEquals(Str::slug($productName), $product->slug);
        $this->assertDatabaseHas('products', [
            'name' => $productName,
            'slug' => Str::slug($productName),
        ]);
    }

    public function test_product_slug_is_unique_when_auto_generated(): void
    {
        $productName = 'Duplicate Product Name';
        $expectedSlug = Str::slug($productName);

        // Create first product
        $firstProduct = Product::create([
            'name' => $productName,
            'code' => 'PROD-1111-AAAA',
            'category_id' => $this->category->id,
            'description' => 'First product',
            'digital' => true,
            'price' => 100000,
            'stock' => 999,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals($expectedSlug, $firstProduct->slug);

        // Create second product with same name
        $secondProduct = Product::create([
            'name' => $productName,
            'code' => 'PROD-2222-BBBB',
            'category_id' => $this->category->id,
            'description' => 'Second product',
            'digital' => true,
            'price' => 100000,
            'stock' => 999,
            'user_id' => $this->user->id,
        ]);

        // Second product should have unique slug with counter
        $this->assertNotEquals($expectedSlug, $secondProduct->slug);
        $this->assertEquals($expectedSlug . '-1', $secondProduct->slug);
    }

    public function test_product_update_preserves_existing_slug(): void
    {
        $product = Product::create([
            'name' => 'Original Name',
            'code' => 'PROD-3333-CCCC',
            'category_id' => $this->category->id,
            'description' => 'Original description',
            'digital' => true,
            'price' => 100000,
            'stock' => 999,
            'user_id' => $this->user->id,
        ]);

        $originalSlug = $product->slug;
        $this->assertEquals('original-name', $originalSlug);

        // Update product name
        $product->update([
            'name' => 'Updated Name',
            'price' => 200000,
        ]);

        // Slug should remain unchanged when updating (slug field has value)
        $this->assertEquals($originalSlug, $product->slug);
        $this->assertEquals('Updated Name', $product->name);
    }

    public function test_product_auto_generates_slug_on_update_if_empty(): void
    {
        // Create product with slug
        $product = Product::create([
            'name' => 'Product With Slug',
            'slug' => 'custom-slug',
            'code' => 'PROD-4444-DDDD',
            'category_id' => $this->category->id,
            'description' => 'Product description',
            'digital' => true,
            'price' => 100000,
            'stock' => 999,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals('custom-slug', $product->slug);

        // Update and clear the slug
        $product->slug = '';
        $product->name = 'New Product Name';
        $product->save();

        // Should auto-generate slug from new name
        $this->assertEquals('new-product-name', $product->slug);
    }

    public function test_category_auto_generates_slug_from_name(): void
    {
        $categoryName = 'Test Category Name';

        $category = Category::create([
            'name' => $categoryName,
            'is_active' => true,
        ]);

        $this->assertEquals(Str::slug($categoryName), $category->slug);
        $this->assertDatabaseHas('categories', [
            'name' => $categoryName,
            'slug' => Str::slug($categoryName),
        ]);
    }

    public function test_category_slug_is_unique_when_auto_generated(): void
    {
        $categoryName = 'Duplicate Category';
        $expectedSlug = Str::slug($categoryName);

        // Create first category
        $firstCategory = Category::create([
            'name' => $categoryName,
            'is_active' => true,
        ]);

        $this->assertEquals($expectedSlug, $firstCategory->slug);

        // Create second category with same name
        $secondCategory = Category::create([
            'name' => $categoryName,
            'is_active' => true,
        ]);

        // Second category should have unique slug with counter
        $this->assertNotEquals($expectedSlug, $secondCategory->slug);
        $this->assertEquals($expectedSlug . '-1', $secondCategory->slug);
    }
}
