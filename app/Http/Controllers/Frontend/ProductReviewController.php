<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductRatingResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Services\ProductReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

final class ProductReviewController extends Controller
{
    public function __construct(private readonly ProductReviewService $reviewService) {}

    public function index(Request $request, Product $product): JsonResponse
    {
        $result = $this->reviewService->listing($product, $request->integer('rating') ?: null);
        $reviews = $result['reviews'];

        return response()->json([
            'data' => ProductRatingResource::make([
                'summary' => $result['summary'],
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
        $customer = $this->customer();
        abort_unless($transaction->customer_id === $customer->getKey(), 403);
        abort_unless($transaction->status === TransactionStatus::completed, 422, 'Ulasan hanya dapat ditambahkan setelah pesanan selesai.');
        abort_unless($transactionProduct->transaction_id === $transaction->getKey(), 404);
        abort_unless($transactionProduct->customer_id === $customer->getKey(), 403);

        $validated = $request->validate([
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

        $images = array_values(array_filter(
            $request->file('images', []),
            static fn (mixed $image): bool => $image instanceof UploadedFile,
        ));
        $created = $this->reviewService->createReview(
            $customer,
            $transactionProduct,
            (int) $validated['rating'],
            isset($validated['review']) ? (string) $validated['review'] : null,
            (bool) ($validated['is_anonymous'] ?? false),
            $images,
        );

        if (! $created) {
            return back()->with('error', 'Anda sudah memberikan ulasan untuk produk ini.');
        }

        return back()->with('success', 'Ulasan produk berhasil ditambahkan.');
    }

    public function storeBatch(Request $request, Transaction $transaction): RedirectResponse
    {
        $customer = $this->customer();
        abort_unless($transaction->customer_id === $customer->getKey(), 403);
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

        /** @var list<array{transaction_product_id: string, rating: int, review: ?string, is_anonymous: bool, images: list<UploadedFile>}> $reviews */
        $reviews = array_map(
            static function (array $review): array {
                $images = array_values(array_filter(
                    $review['images'] ?? [],
                    static fn (mixed $image): bool => $image instanceof UploadedFile,
                ));

                return [
                    'transaction_product_id' => (string) $review['transaction_product_id'],
                    'rating' => (int) $review['rating'],
                    'review' => isset($review['review']) ? (string) $review['review'] : null,
                    'is_anonymous' => (bool) ($review['is_anonymous'] ?? false),
                    'images' => $images,
                ];
            },
            $data['reviews'],
        );

        $this->reviewService->createBatch($customer, $transaction, $reviews);

        return back()->with('success', 'Ulasan produk berhasil ditambahkan.');
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
