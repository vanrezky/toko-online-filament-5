<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductReviewRepository
{
    /** @return array{average: float, count: int, customer_count: int, admin_count: int, customer_average: float, admin_average: float, distribution: array<int, int>} */
    public function ratingSummary(Product $product): array
    {
        $aggregate = $product->reviews()
            ->selectRaw('COUNT(*) as review_count, COALESCE(AVG(rating), 0) as rating_average')
            ->selectRaw('SUM(CASE WHEN is_admin = 0 THEN 1 ELSE 0 END) as customer_count')
            ->selectRaw('SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin_count')
            ->selectRaw('COALESCE(AVG(CASE WHEN is_admin = 0 THEN rating END), 0) as customer_average')
            ->selectRaw('COALESCE(AVG(CASE WHEN is_admin = 1 THEN rating END), 0) as admin_average')
            ->selectRaw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1_count')
            ->selectRaw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2_count')
            ->selectRaw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3_count')
            ->selectRaw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4_count')
            ->selectRaw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5_count')
            ->first();

        return [
            'average' => round((float) $aggregate->rating_average, 1),
            'count' => (int) $aggregate->review_count,
            'customer_count' => (int) $aggregate->customer_count,
            'admin_count' => (int) $aggregate->admin_count,
            'customer_average' => round((float) $aggregate->customer_average, 1),
            'admin_average' => round((float) $aggregate->admin_average, 1),
            'distribution' => collect(range(1, 5))->mapWithKeys(
                fn (int $value): array => [$value => (int) $aggregate->{'rating_'.$value.'_count'}],
            )->all(),
        ];
    }

    public function paginate(Product $product, ?int $rating, int $perPage = 10): LengthAwarePaginator
    {
        return $product->reviews()
            ->select(['id', 'product_id', 'customer_id', 'rating', 'review', 'reviewer_name', 'is_anonymous', 'is_admin', 'created_at'])
            ->with(['customer:id,first_name,last_name', 'media'])
            ->when($rating !== null && $rating >= 1 && $rating <= 5, fn (Builder $query): Builder => $query->where('rating', $rating))
            ->latest()
            ->paginate($perPage);
    }

    /** @param list<string> $uuids
     * @return Collection<int, TransactionProduct>
     */
    public function transactionProducts(Transaction $transaction, int $customerId, array $uuids): Collection
    {
        return $transaction->products()
            ->where('customer_id', $customerId)
            ->whereIn('uuid', $uuids)
            ->get();
    }

    public function findReview(TransactionProduct $transactionProduct): ?ProductReview
    {
        return ProductReview::query()->where('transaction_product_id', $transactionProduct->getKey())->first();
    }

    /** @param array{transaction_product_id: int, product_id: int, customer_id: int, rating: int, review: ?string, is_anonymous: bool, is_admin: bool} $attributes */
    public function firstOrCreateReview(array $attributes): ProductReview
    {
        return ProductReview::query()->firstOrCreate(
            ['transaction_product_id' => $attributes['transaction_product_id']],
            $attributes,
        );
    }
}
