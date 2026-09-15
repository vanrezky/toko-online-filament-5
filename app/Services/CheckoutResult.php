<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;

final class CheckoutResult
{
    /**
     * @param  array{provider: string, payment_url: ?string, snap_token: ?string, client_key: ?string, mode: ?string, error: ?string}  $payment
     */
    public function __construct(
        public readonly Transaction $transaction,
        public readonly array $payment,
    ) {}
}
