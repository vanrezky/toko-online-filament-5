<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StorefrontDemoCatalogSeeder extends Seeder
{
    private const TARGET_PRODUCT_COUNT = 50;

    /**
     * Seed enough active catalog entries for storefront layout development.
     */
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Elektronik', 'slug' => 'elektronik'],
            ['name' => 'Fashion Pria', 'slug' => 'fashion-pria'],
            ['name' => 'Fashion Wanita', 'slug' => 'fashion-wanita'],
            ['name' => 'Kebutuhan Rumah', 'slug' => 'kebutuhan-rumah'],
            ['name' => 'Kesehatan & Kecantikan', 'slug' => 'kesehatan-kecantikan'],
            ['name' => 'Olahraga', 'slug' => 'olahraga'],
        ])->map(fn (array $category) => Category::query()->firstOrCreate(
            ['slug' => $category['slug']],
            [...$category, 'is_active' => true, 'is_featured' => true],
        ));

        $demoProducts = Product::query()
            ->with('category:id,name')
            ->where('code', 'like', 'DEMO-%')
            ->orderBy('id')
            ->get();

        $demoProducts->each(function (Product $product, int $index): void {
            $product->update($this->demoCopy($product->category?->name ?? 'Katalog', $index + 1));
        });

        $productsNeeded = max(0, self::TARGET_PRODUCT_COUNT - Product::query()->count());

        if ($productsNeeded === 0) {
            return;
        }

        $user = User::query()->firstOrFail();
        $warehouse = Warehouse::active()->first();

        Product::factory()
            ->count($productsNeeded)
            ->sequence(function ($sequence) use ($categories, $demoProducts): array {
                $category = $categories[$sequence->index % $categories->count()];
                $number = $demoProducts->count() + $sequence->index + 1;

                return [
                    'category_id' => $category->id,
                    'code' => sprintf('DEMO-%04d', Product::query()->max('id') + $sequence->index + 1),
                    ...$this->demoCopy($category->name, $number),
                ];
            })
            ->create([
                'user_id' => $user->id,
                'warehouse_id' => $warehouse?->id,
                'is_active' => true,
            ]);
    }

    private function demoCopy(string $categoryName, int $number): array
    {
        $name = "Produk Demo Premium {$categoryName} untuk Kebutuhan Harian Berkualitas Nomor {$number}";

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => "Produk demo premium untuk kategori {$categoryName} ini dibuat agar tampilan katalog dapat diuji dengan konten yang lebih realistis dan panjang. Produk nomor {$number} memiliki informasi yang cukup untuk menampilkan beberapa baris teks pada kartu produk maupun halaman detail.\n\n"
                . "Gunakan data ini untuk mengevaluasi responsivitas judul, jarak antarelemen, hierarki harga, dan kemampuan layout dalam menangani deskripsi panjang. Semua informasi bersifat dummy untuk kebutuhan pengembangan lokal dan tidak merepresentasikan stok atau spesifikasi produk sebenarnya.\n\n"
                . "Dengan konten yang lebih lengkap, tim dapat memeriksa perilaku tampilan pada layar kecil maupun desktop sebelum katalog menggunakan data produk produksi.",
        ];
    }
}
