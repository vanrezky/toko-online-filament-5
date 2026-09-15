<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Repositories\WishlistRepository;
use Illuminate\Database\Eloquent\Collection;

final class WishlistService
{
    public function __construct(private readonly WishlistRepository $wishlistRepository) {}

    /** @return Collection<int, Product> */
    public function products(Customer $customer, ?int $resellerId): Collection
    {
        return $this->wishlistRepository->productsForCustomer($customer, $resellerId);
    }

    /** @return 'added'|'removed' */
    public function toggle(Customer $customer, string $productUuid): string
    {
        $product = $this->wishlistRepository->findByProductUuid($productUuid);
        $wishlist = $this->wishlistRepository->findItem($customer, $product);

        if ($wishlist !== null) {
            $this->wishlistRepository->remove($wishlist);

            return 'removed';
        }

        $this->wishlistRepository->add($customer, $product);

        return 'added';
    }
}
