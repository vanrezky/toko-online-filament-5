<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Voucher;
use App\Repositories\CartRepository;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Cookie;

final class FrontendVoucherService
{
    public function __construct(
        private readonly VoucherService $voucherService,
        private readonly VoucherCookieService $cookieService,
        private readonly CartRepository $cartRepository,
    ) {}

    /** @return Collection<int, Voucher> */
    public function publicVouchers(?string $type): Collection
    {
        return $this->voucherService->getPublicVouchers($type);
    }

    /** @return array{shipping: array<string, int|float|string|bool|null>|null, product: array<string, int|float|string|bool|null>|null} */
    public function pendingVouchers(): array
    {
        return $this->cookieService->get();
    }

    public function activeCart(Customer $customer): ?Cart
    {
        return $this->cartRepository->activeForCustomer($customer);
    }

    /** @return array{voucher: Voucher|null, cookie: Cookie|null, error: string|null} */
    public function apply(Customer $customer, string $code): array
    {
        $cart = $this->activeCart($customer);
        if ($cart === null) {
            return ['voucher' => null, 'cookie' => null, 'error' => __('messages.error.cart_not_found')];
        }

        $result = $this->voucherService->validateVoucher($code, $cart, $customer);
        if (! $result->success || $result->voucher === null) {
            return [
                'voucher' => null,
                'cookie' => null,
                'error' => $result->errorMessage ?? __('messages.error.voucher_invalid_code'),
            ];
        }

        $type = $result->voucher->is_shipping ? 'shipping' : 'product';
        $stored = $this->cookieService->saveVoucher($type, [
            'code' => $result->voucher->code,
            'name' => $result->voucher->name,
            'type' => $type,
        ]);

        return ['voucher' => $result->voucher, 'cookie' => $stored['cookie'], 'error' => null];
    }

    /** @return array{cookie: Cookie, vouchers: array{shipping: array<string, int|float|string|bool|null>|null, product: array<string, int|float|string|bool|null>|null}} */
    public function remove(string $type): array
    {
        return $this->cookieService->removeVoucher($type);
    }

    /** @return array<string, int|float|string|bool|null> */
    public function formatVoucher(Voucher $voucher): array
    {
        return [
            'id' => (int) $voucher->getKey(),
            'uuid' => $voucher->uuid,
            'name' => $voucher->name,
            'description' => $voucher->description,
            'code' => $voucher->code,
            'type' => $voucher->voucher_type->value,
            'type_label' => $voucher->voucher_type->getLabel(),
            'discount_type' => $voucher->discount_type->value,
            'discount_type_label' => $voucher->discount_type->getLabel(),
            'discount' => $voucher->discount,
            'formatted_discount' => $voucher->formatted_discount,
            'discount_min' => $voucher->discount_min,
            'discount_max' => $voucher->discount_max,
            'min_purchase_formatted' => $voucher->min_purchase_formatted,
            'is_shipping' => $voucher->is_shipping,
            'is_product' => $voucher->is_product,
            'is_expiring_soon' => $voucher->is_expiring_soon,
            'remaining_days' => $voucher->remaining_days,
            'remaining_hours' => $voucher->remaining_hours,
            'usage_count' => $voucher->usage_count,
            'max_user_used' => $voucher->max_user_used,
            'usage_percentage' => $voucher->usage_percentage,
            'is_fully_used' => $voucher->is_fully_used,
            'start_at' => $voucher->start_at,
            'end_at' => $voucher->end_at,
            'validity_period' => $voucher->validity_period,
            'image' => $voucher->getFirstMediaUrl() ?: null,
            'image_thumb' => $voucher->getFirstMediaUrl('thumb') ?: null,
        ];
    }
}
