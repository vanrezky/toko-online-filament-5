<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductFlashsale;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FlashsalePricingService
{
    /**
     * Resolve the only price that may be charged for an item.
     * Flashsale deliberately wins over wholesale and the product sale price.
     *
     * @return array{price: float, discount: float, original_price: float, source: string, flashsale_product_id: ?int, flashsale_discount_percentage: ?float, flashsale_stock: ?int}
     */
    public function resolve(Product $product, ?ProductVariant $variant, int $quantity, ?ProductFlashsale $flashsaleProduct = null): array
    {
        $flashsaleProduct ??= $this->activeProduct($product->id);

        if ($flashsaleProduct && $flashsaleProduct->stock > 0) {
            return $this->resolveFlashsale($product, $variant, $flashsaleProduct);
        }

        $originalPrice = (float) ($variant?->price ?? $product->price);
        $wholesale = $product->wholesales()
            ->where('min_qty', '<=', $quantity)
            ->orderByDesc('min_qty')
            ->first();

        if ($wholesale) {
            return $this->result((float) $wholesale->price, (float) $wholesale->price, 'wholesale');
        }

        if (! $variant && filled($product->sale_price) && (float) $product->sale_price < $originalPrice) {
            return $this->result($originalPrice, (float) $product->sale_price, 'sale');
        }

        return $this->result($originalPrice, $originalPrice, 'regular');
    }

    /** @return Collection<int, array{item: mixed, pricing: array}> */
    public function resolveCart(Cart $cart, bool $lockForCheckout = false): Collection
    {
        $items = $cart->items->sortBy('product_id')->values();
        $quantities = $items->groupBy('product_id')->map(fn (Collection $group) => (int) $group->sum('quantity'));
        $flashsaleProducts = $this->activeProducts($quantities->keys()->all(), $lockForCheckout);

        foreach ($quantities as $productId => $quantity) {
            $flashsaleProduct = $flashsaleProducts->get((int) $productId);
            if ($flashsaleProduct && $flashsaleProduct->stock < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => ['Kuota flashsale untuk salah satu produk tidak mencukupi.'],
                ]);
            }
        }

        return $items->map(function ($item) use ($flashsaleProducts) {
            return [
                'item' => $item,
                'pricing' => $this->resolve(
                    $item->product,
                    $item->productVariant,
                    (int) $item->quantity,
                    $flashsaleProducts->get($item->product_id),
                ),
            ];
        });
    }

    public function syncCart(Cart $cart): Collection
    {
        $resolved = $this->resolveCart($cart);

        foreach ($resolved as $entry) {
            $entry['item']->update([
                'price' => $entry['pricing']['price'],
                'discount' => $entry['pricing']['discount'],
            ]);
        }

        return $resolved;
    }

    public function resolveFlashsale(Product $product, ?ProductVariant $variant, ProductFlashsale $flashsaleProduct): array
    {
        $originalPrice = (float) ($variant?->price ?? $product->price);
        $finalPrice = round($originalPrice * (1 - ((float) $flashsaleProduct->discount_percentage / 100)), 2);

        return array_merge($this->result($originalPrice, $finalPrice, 'flashsale'), [
            'flashsale_product_id' => $flashsaleProduct->id,
            'flashsale_discount_percentage' => (float) $flashsaleProduct->discount_percentage,
            'flashsale_stock' => (int) $flashsaleProduct->stock,
        ]);
    }

    private function activeProduct(int $productId): ?ProductFlashsale
    {
        return $this->activeProducts([$productId])->first();
    }

    private function activeProducts(array $productIds, bool $lockForCheckout = false): Collection
    {
        $query = ProductFlashsale::query()
            ->whereIn('product_id', $productIds)
            ->whereHas('flashsale', fn ($query) => $query->current())
            ->with('flashsale')
            ->orderBy('product_id')
            ->orderByDesc('id');

        if ($lockForCheckout) {
            $query->lockForUpdate();
        }

        return $query->get()->unique('product_id')->keyBy('product_id');
    }

    private function result(float $originalPrice, float $finalPrice, string $source): array
    {
        return [
            'price' => $finalPrice,
            'discount' => max(0, $originalPrice - $finalPrice),
            'original_price' => $originalPrice,
            'source' => $source,
            'flashsale_product_id' => null,
            'flashsale_discount_percentage' => null,
            'flashsale_stock' => null,
        ];
    }
}
