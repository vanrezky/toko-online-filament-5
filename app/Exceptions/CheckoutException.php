<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class CheckoutException extends RuntimeException
{
    /**
     * @param  array<string, int|float|string|null>  $context
     */
    public function __construct(
        public readonly string $errorKey,
        public readonly array $context = [],
    ) {
        parent::__construct($errorKey);
    }
}
