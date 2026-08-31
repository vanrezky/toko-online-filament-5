<?php

namespace App\Modules\ProductImport\Data;

use Carbon\CarbonImmutable;

final readonly class ProductImportPreview
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, ProductImportValidationError>  $errors
     */
    public function __construct(
        public array $rows,
        public array $errors,
        public ?string $token,
        public ?CarbonImmutable $expiresAt,
    ) {}

    /** @return array{rows:array<int,array<string,mixed>>,errors:array<int,array{row:int,column:string,message:string}>,token:?string,expires_at:?string} */
    public function toArray(): array
    {
        return [
            'rows' => $this->rows,
            'errors' => array_map(fn (ProductImportValidationError $error): array => $error->toArray(), $this->errors),
            'token' => $this->token,
            'expires_at' => $this->expiresAt?->toIso8601String(),
        ];
    }
}
