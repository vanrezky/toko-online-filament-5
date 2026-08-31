<?php

namespace App\Modules\ProductImport\Data;

final readonly class ProductImportValidationError
{
    public function __construct(
        public int $row,
        public string $column,
        public string $message,
    ) {}

    /** @return array{row:int,column:string,message:string} */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'column' => $this->column,
            'message' => $this->message,
        ];
    }
}
