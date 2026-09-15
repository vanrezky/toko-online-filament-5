<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Repositories\ProductReviewRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ProductReviewService
{
    public function __construct(private readonly ProductReviewRepository $reviewRepository) {}

    /** @return array{summary: array{average: float, count: int, customer_count: int, admin_count: int, customer_average: float, admin_average: float, distribution: array<int, int>}, reviews: LengthAwarePaginator} */
    public function listing(Product $product, ?int $rating): array
    {
        $summary = CacheService::rememberManaged(
            'product-stats',
            "product-rating-summary:{$product->getKey()}",
            3600,
            fn (): array => $this->reviewRepository->ratingSummary($product),
        );

        return [
            'summary' => is_array($summary) ? $summary : $this->reviewRepository->ratingSummary($product),
            'reviews' => $this->reviewRepository->paginate($product, $rating),
        ];
    }

    /** @param list<UploadedFile> $images */
    public function createReview(Customer $customer, TransactionProduct $transactionProduct, int $rating, ?string $review, bool $anonymous, array $images): bool
    {
        if ($this->reviewRepository->findReview($transactionProduct) !== null) {
            return false;
        }

        $productReview = $this->reviewRepository->firstOrCreateReview([
            'transaction_product_id' => (int) $transactionProduct->getKey(),
            'product_id' => (int) $transactionProduct->product_id,
            'customer_id' => (int) $customer->getKey(),
            'rating' => $rating,
            'review' => $review,
            'is_anonymous' => $anonymous,
            'is_admin' => false,
        ]);

        $this->attachImages($productReview, $images);

        return $productReview->wasRecentlyCreated;
    }

    /** @param list<array{transaction_product_id: string, rating: int, review: ?string, is_anonymous: bool, images: list<UploadedFile>}> $reviews */
    public function createBatch(Customer $customer, Transaction $transaction, array $reviews): void
    {
        $uuids = array_map(static fn (array $review): string => $review['transaction_product_id'], $reviews);
        $products = $this->reviewRepository->transactionProducts($transaction, (int) $customer->getKey(), $uuids)->keyBy('uuid');

        if ($products->count() !== count($reviews)) {
            throw (new ModelNotFoundException)->setModel(TransactionProduct::class);
        }

        DB::transaction(function () use ($customer, $reviews, $products): void {
            foreach ($reviews as $reviewData) {
                $transactionProduct = $products->get($reviewData['transaction_product_id']);

                if (! $transactionProduct instanceof TransactionProduct || $this->reviewRepository->findReview($transactionProduct) !== null) {
                    continue;
                }

                $review = $this->reviewRepository->firstOrCreateReview([
                    'transaction_product_id' => (int) $transactionProduct->getKey(),
                    'product_id' => (int) $transactionProduct->product_id,
                    'customer_id' => (int) $customer->getKey(),
                    'rating' => $reviewData['rating'],
                    'review' => $reviewData['review'],
                    'is_anonymous' => $reviewData['is_anonymous'],
                    'is_admin' => false,
                ]);
                $this->attachImages($review, $reviewData['images']);
            }
        });
    }

    /** @param list<UploadedFile> $images */
    private function attachImages(ProductReview $review, array $images): void
    {
        foreach ($images as $image) {
            $review->addMedia($image)->toMediaCollection('images', config('filesystems.upload_disk'));
        }
    }
}
