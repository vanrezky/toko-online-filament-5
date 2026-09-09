<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ProductInventoryService
{
    public function effectiveStock(Product $product, ?ProductVariant $variant = null): int
    {
        return (int) ($variant?->stock ?? $product->stock);
    }

    public function assertAvailable(
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        string $field = 'quantity',
    ): void {
        $hasVariants = $this->hasVariants($product);

        if (($hasVariants && ! $variant) || ($variant && (int) $variant->product_id !== (int) $product->id)) {
            throw ValidationException::withMessages([
                'product_variant_id' => [__('messages.error.invalid_product_variant')],
            ]);
        }

        if ($quantity > $this->effectiveStock($product, $variant)) {
            throw ValidationException::withMessages([
                $field => [__('messages.error.stock_insufficient')],
            ]);
        }
    }

    public function syncProductStock(Product|int $product, bool $resetWhenEmpty = false): void
    {
        $product = $product instanceof Product
            ? $product
            : Product::query()->find($product);

        if (! $product) {
            return;
        }

        $variantQuery = ProductVariant::query()->where('product_id', $product->id);

        if (! $variantQuery->exists()) {
            if ($resetWhenEmpty && (int) $product->stock !== 0) {
                $product->update(['stock' => 0]);
            }

            return;
        }

        $stock = (int) $variantQuery->sum('stock');

        if ((int) $product->stock !== $stock) {
            $product->update(['stock' => $stock]);
        }
    }

    public function reserve(Transaction $transaction, Collection $resolvedItems): void
    {
        $lines = $resolvedItems
            ->map(function (array $entry): array {
                $item = $entry['item'];

                return [
                    'product_id' => (int) $item->product_id,
                    'variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                    'quantity' => (int) $item->quantity,
                ];
            })
            ->values();

        if ($lines->isEmpty()) {
            return;
        }

        $productIds = $lines->pluck('product_id')->unique()->sort()->values();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $variantsByProduct = ProductVariant::query()
            ->whereIn('product_id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->groupBy('product_id');

        $linesByProduct = $lines->groupBy('product_id');

        foreach ($productIds as $productId) {
            $product = $products->get($productId);
            $productLines = $linesByProduct->get($productId, collect());
            $productVariants = $variantsByProduct->get($productId, collect());
            $requestedProductQuantity = (int) $productLines->sum('quantity');

            if (! $product) {
                throw ValidationException::withMessages([
                    'cart' => [__('messages.error.stock_insufficient')],
                ]);
            }

            if ($productVariants->isNotEmpty()) {
                $aggregateStock = (int) $productVariants->sum('stock');

                if ((int) $product->stock !== $aggregateStock) {
                    $product->update(['stock' => $aggregateStock]);
                }

                if ($requestedProductQuantity > $aggregateStock) {
                    $this->throwInsufficientStock('cart');
                }

                $requestedByVariant = $productLines->groupBy('variant_id')->map(
                    fn (Collection $variantLines): int => (int) $variantLines->sum('quantity'),
                );

                foreach ($requestedByVariant as $variantId => $quantity) {
                    $variant = $productVariants->firstWhere('id', (int) $variantId);

                    if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                        throw ValidationException::withMessages([
                            'cart' => [__('messages.error.invalid_product_variant')],
                        ]);
                    }

                    if ((int) $variant->stock < $quantity) {
                        $this->throwInsufficientStock('cart');
                    }
                }
            } elseif ($productLines->contains(fn (array $line): bool => $line['variant_id'] !== null)) {
                throw ValidationException::withMessages([
                    'cart' => [__('messages.error.invalid_product_variant')],
                ]);
            } elseif ((int) $product->stock < $requestedProductQuantity) {
                $this->throwInsufficientStock('cart');
            }
        }

        foreach ($productIds as $productId) {
            $product = $products->get($productId);
            $productLines = $linesByProduct->get($productId, collect());
            $productVariants = $variantsByProduct->get($productId, collect());
            $requestedProductQuantity = (int) $productLines->sum('quantity');

            if ($productVariants->isNotEmpty()) {
                $requestedByVariant = $productLines->groupBy('variant_id')->map(
                    fn (Collection $variantLines): int => (int) $variantLines->sum('quantity'),
                );

                foreach ($requestedByVariant as $variantId => $quantity) {
                    $variant = $productVariants->firstWhere('id', (int) $variantId);
                    $variant->stock = (int) $variant->stock - $quantity;
                    $variant->saveQuietly();
                }

                $product->stock = (int) $productVariants->sum('stock');
                $product->save();
            } else {
                $product->stock = (int) $product->stock - $requestedProductQuantity;
                $product->save();
            }
        }
    }

    public function release(Transaction $transaction): void
    {
        $transaction->loadMissing('products.productVariant');
        $lines = $transaction->products;

        if ($lines->isEmpty()) {
            return;
        }

        $productIds = $lines->pluck('product_id')->filter()->unique()->sort()->values();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $variantIds = $lines->pluck('product_variant_id')->filter()->unique()->sort()->values();
        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($lines->groupBy('product_id') as $productId => $productLines) {
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            foreach ($productLines as $line) {
                $variant = $line->product_variant_id ? $variants->get($line->product_variant_id) : null;

                if ($variant) {
                    $variant->stock = (int) $variant->stock + (int) $line->quantity;
                    $variant->saveQuietly();
                }
            }

            $product->stock = (int) $product->stock + (int) $productLines->sum('quantity');
            $product->save();
        }
    }

    private function hasVariants(Product $product): bool
    {
        if (array_key_exists('product_variants_count', $product->getAttributes())) {
            return (int) $product->product_variants_count > 0;
        }

        return $product->productVariants()->exists();
    }

    private function throwInsufficientStock(string $field): never
    {
        throw ValidationException::withMessages([
            $field => [__('messages.error.stock_insufficient')],
        ]);
    }
}
