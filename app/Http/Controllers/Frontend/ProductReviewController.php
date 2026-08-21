<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductRatingResource;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductReviewController extends Controller
{
    public function index(Product $product)
    {
        $rating = request()->integer('rating');

        $summary = CacheService::rememberManaged('product-stats', "product-rating-summary:{$product->id}", 3600, function () use ($product): array {
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
                'distribution' => collect(range(1, 5))->mapWithKeys(fn (int $value) => [
                    $value => (int) $aggregate->{'rating_'.$value.'_count'},
                ]),
            ];
        });

        $reviews = $product->reviews()
            ->select(['id', 'product_id', 'customer_id', 'rating', 'review', 'reviewer_name', 'is_anonymous', 'is_admin', 'created_at'])
            ->with(['customer:id,first_name,last_name', 'media'])
            ->when($rating >= 1 && $rating <= 5, fn ($query) => $query->where('rating', $rating))
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => ProductRatingResource::make([
                'summary' => $summary,
                'reviews' => $reviews->getCollection(),
            ])->resolve(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, Transaction $transaction, TransactionProduct $transactionProduct): RedirectResponse
    {
        abort_unless($transaction->customer_id === Auth::guard('customer')->id(), 403);
        abort_unless($transaction->status === TransactionStatus::completed, 422, 'Ulasan hanya dapat ditambahkan setelah pesanan selesai.');
        abort_unless($transactionProduct->transaction_id === $transaction->id, 404);
        abort_unless($transactionProduct->customer_id === Auth::guard('customer')->id(), 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => [
                'nullable',
                'string',
                'max:7000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (str_word_count(strip_tags((string) $value)) > 1000) {
                        $fail('Ulasan maksimal 1000 kata.');
                    }
                },
            ],
            'is_anonymous' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $review = ProductReview::firstOrCreate([
            'transaction_product_id' => $transactionProduct->id,
        ], [
            'product_id' => $transactionProduct->product_id,
            'customer_id' => Auth::guard('customer')->id(),
            'rating' => $data['rating'],
            'review' => $data['review'] ?? null,
            'is_anonymous' => $request->boolean('is_anonymous'),
            'is_admin' => false,
        ]);

        if (! $review->wasRecentlyCreated) {
            return back()->with('error', 'Anda sudah memberikan ulasan untuk produk ini.');
        }

        foreach ($request->file('images', []) as $image) {
            $review->addMedia($image)->toMediaCollection('images');
        }

        return back()->with('success', 'Ulasan produk berhasil ditambahkan.');
    }

    public function storeBatch(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->customer_id === Auth::guard('customer')->id(), 403);
        abort_unless($transaction->status === TransactionStatus::completed, 422, 'Ulasan hanya dapat ditambahkan setelah pesanan selesai.');

        $data = $request->validate([
            'reviews' => ['required', 'array', 'min:1'],
            'reviews.*.transaction_product_id' => ['required', 'uuid', 'distinct'],
            'reviews.*.rating' => ['required', 'integer', 'between:1,5'],
            'reviews.*.review' => [
                'nullable',
                'string',
                'max:7000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (str_word_count(strip_tags((string) $value)) > 1000) {
                        $fail('Ulasan maksimal 1000 kata.');
                    }
                },
            ],
            'reviews.*.is_anonymous' => ['nullable', 'boolean'],
            'reviews.*.images' => ['nullable', 'array', 'max:3'],
            'reviews.*.images.*' => ['image', 'max:5120'],
        ]);

        $products = $transaction->products()
            ->where('customer_id', Auth::guard('customer')->id())
            ->whereIn('uuid', collect($data['reviews'])->pluck('transaction_product_id'))
            ->get()
            ->keyBy('uuid');

        abort_unless($products->count() === count($data['reviews']), 404);

        DB::transaction(function () use ($data, $products): void {
            foreach ($data['reviews'] as $reviewData) {
                $transactionProduct = $products->get($reviewData['transaction_product_id']);

                $review = ProductReview::firstOrCreate([
                    'transaction_product_id' => $transactionProduct->id,
                ], [
                    'product_id' => $transactionProduct->product_id,
                    'customer_id' => Auth::guard('customer')->id(),
                    'rating' => $reviewData['rating'],
                    'review' => $reviewData['review'] ?? null,
                    'is_anonymous' => (bool) ($reviewData['is_anonymous'] ?? false),
                    'is_admin' => false,
                ]);

                if (! $review->wasRecentlyCreated) {
                    continue;
                }

                foreach ($reviewData['images'] ?? [] as $image) {
                    $review->addMedia($image)->toMediaCollection('images');
                }
            }
        });

        return back()->with('success', 'Ulasan produk berhasil ditambahkan.');
    }
}
