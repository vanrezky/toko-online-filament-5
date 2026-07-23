<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Seeder;

class ProductReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviewCounts = [10, 20, 30];
        $names = ['Jihan H.', 'Silvi K.', 'Citra Pratiwi', 'Dewi S.', 'Wulan P.', 'Salsabila Lestari'];
        $reviews = [
            'Produk sesuai deskripsi, kualitasnya bagus dan pengiriman rapi.',
            'Seller amanah, produk diterima dalam kondisi baik.',
            'Sangat puas, akan membeli lagi.',
            'Bahannya bagus dan nyaman digunakan.',
            'Pesanan cepat sampai, sesuai ekspektasi.',
        ];

        Product::query()->orderBy('id')->take(count($reviewCounts))->get()
            ->each(function (Product $product, int $productIndex) use ($reviewCounts, $names, $reviews): void {
                ProductReview::query()
                    ->where('product_id', $product->id)
                    ->where('is_admin', true)
                    ->where('reviewer_name', 'like', 'Seeder Review %')
                    ->delete();

                foreach (range(1, $reviewCounts[$productIndex]) as $number) {
                    ProductReview::create([
                        'product_id' => $product->id,
                        'rating' => $number % 9 === 0 ? 4 : 5,
                        'review' => $reviews[($number - 1) % count($reviews)],
                        'reviewer_name' => 'Seeder Review ' . $names[($number - 1) % count($names)],
                        'is_admin' => true,
                    ]);
                }
            });
    }
}
