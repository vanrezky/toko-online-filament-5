<?php

namespace Tests\Feature;

require_once getcwd().'/app/Services/ProductImageUrlImporter.php';

use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Product;
use App\Services\ProductImageUrlImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductImageUrlImporterTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('r2');
        config([
            'filesystems.upload_disk' => 'r2',
            'media-library.queue_conversions_by_default' => false,
        ]);
    }

    public function test_it_imports_a_public_image_url_and_serializes_it_through_existing_product_resources(): void
    {
        Http::fake([
            'https://images.example.test/product.png' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $product = Product::factory()->create();

        $this->importer()->import($product, [['url' => 'https://images.example.test/product.png']]);

        $product = $product->fresh();
        $this->assertCount(1, $product->getMedia());
        $media = $product->getFirstMedia();
        $this->assertSame('r2', $media->disk);
        $this->assertSame(
            "uploads/products/{$media->id}/product.png",
            $media->getPathRelativeToRoot()
        );

        $productResponse = (new ProductResource($product))->resolve();
        $simpleResponse = (new ProductSimpleResource($product))->resolve();

        $this->assertNotEmpty($productResponse['thumbnail']);
        $this->assertCount(1, $productResponse['images']);
        $this->assertNotEmpty($simpleResponse['thumbnail']);
    }

    public function test_it_rejects_malformed_private_unavailable_and_non_image_urls_without_adding_media(): void
    {
        Http::fake([
            'https://images.example.test/missing.png' => Http::response('', 404),
            'https://images.example.test/not-an-image' => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        foreach ([
            'not-a-url',
            'http://127.0.0.1/private.png',
            'https://images.example.test/missing.png',
            'https://images.example.test/not-an-image',
        ] as $url) {
            $product = Product::factory()->create();

            try {
                $this->importer()->import($product, [['url' => $url]]);
                $this->fail("Expected {$url} to be rejected.");
            } catch (ValidationException) {
                $this->assertCount(0, $product->fresh()->getMedia());
            }
        }
    }

    public function test_it_rejects_urls_that_exceed_the_combined_image_limit(): void
    {
        $product = Product::factory()->create();

        foreach (range(1, 3) as $number) {
            $product->addMediaFromString($this->png())
                ->usingFileName("uploaded-{$number}.png")
                ->toMediaCollection();
        }

        $this->expectException(ValidationException::class);

        $this->importer()->import($product, [
            ['url' => 'https://images.example.test/one.png'],
            ['url' => 'https://images.example.test/two.png'],
            ['url' => 'https://images.example.test/three.png'],
        ]);
    }

    public function test_it_keeps_uploaded_and_linked_images_in_the_same_collection(): void
    {
        Http::fake([
            'https://images.example.test/linked.png' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $product = Product::factory()->create();
        $product->addMediaFromString($this->png())
            ->usingFileName('uploaded.png')
            ->toMediaCollection();

        $this->importer()->import($product, [['url' => 'https://images.example.test/linked.png']]);

        $this->assertCount(2, $product->fresh()->getMedia());
        $this->assertSame('r2', $product->fresh()->getMedia()->last()->disk);
    }

    private function importer(): ProductImageUrlImporter
    {
        return new ProductImageUrlImporter(
            app(HttpFactory::class),
            fn (): array => ['93.184.216.34'],
        );
    }

    private function png(): string
    {
        return base64_decode(self::PNG);
    }
}
