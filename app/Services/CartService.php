<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Repositories\CartRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CartService
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly CartRecommendationService $recommendationService,
        private readonly FlashsalePricingService $pricingService,
        private readonly ProductInventoryService $inventoryService,
    ) {}

    public function activeCart(Customer $customer): ?Cart
    {
        $cart = $this->cartRepository->activeForCustomer($customer);

        if ($cart !== null) {
            $this->pricingService->syncCart($cart);
        }

        return $cart;
    }

    public function recommendations(Cart $cart, ?int $resellerId): Collection
    {
        return $this->recommendationService->forCart($cart, $resellerId);
    }

    /** @return array{cart_item_id: string, cart_count: int} */
    public function addItem(Customer $customer, string $productUuid, ?string $variantUuid, int $quantity): array
    {
        return DB::transaction(function () use ($customer, $productUuid, $variantUuid, $quantity): array {
            $product = $this->cartRepository->findProductForUpdate($productUuid);
            $variant = $variantUuid !== null ? $this->cartRepository->findVariantForUpdate($variantUuid) : null;

            if ($variantUuid !== null && ($variant === null || $variant->product_id !== $product->getKey())) {
                throw ValidationException::withMessages(['product_variant_id' => [__('messages.error.invalid_product_variant')]]);
            }

            $cart = $this->cartRepository->lockActiveForCustomer($customer);
            $item = $this->cartRepository->findItemForUpdate($cart, $product, $variant);
            $newQuantity = ($item?->quantity ?? 0) + $quantity;
            $this->inventoryService->assertAvailable($product, $variant, $newQuantity);
            $priceInfo = $product->calculatePrice($newQuantity, $variant);

            if ($item !== null) {
                $this->cartRepository->updateItem($item, [
                    'quantity' => $newQuantity,
                    'price' => $priceInfo['price'],
                    'discount' => $priceInfo['discount'],
                ]);
            } else {
                $item = $this->cartRepository->createItem(
                    $cart,
                    $product,
                    $variant,
                    $newQuantity,
                    $priceInfo['price'],
                    $priceInfo['discount'],
                );
            }

            return [
                'cart_item_id' => (string) $item->uuid,
                'cart_count' => (int) $cart->items()->sum('quantity'),
            ];
        });
    }

    public function updateItem(Customer $customer, CartItem $item, int $quantity): void
    {
        if (! $this->cartRepository->ownsActiveItem($customer, $item)) {
            throw (new ModelNotFoundException)->setModel(CartItem::class, [$item->getKey()]);
        }

        DB::transaction(function () use ($item, $quantity): void {
            $lockedItem = $this->cartRepository->lockItem($item);
            $product = $this->cartRepository->lockProduct((int) $lockedItem->product_id);
            $variant = $lockedItem->product_variant_id
                ? $this->cartRepository->lockVariant((int) $lockedItem->product_variant_id)
                : null;
            $this->inventoryService->assertAvailable($product, $variant, $quantity);
            $priceInfo = $product->calculatePrice($quantity, $variant);

            $this->cartRepository->updateItem($lockedItem, [
                'quantity' => $quantity,
                'price' => $priceInfo['price'],
                'discount' => $priceInfo['discount'],
            ]);
        });
    }

    public function removeItem(Customer $customer, CartItem $item): void
    {
        if (! $this->cartRepository->ownsActiveItem($customer, $item)) {
            throw (new ModelNotFoundException)->setModel(CartItem::class, [$item->getKey()]);
        }
        $this->cartRepository->deleteItem($item);
    }
}
