<?php

namespace App\Modules\ProductImport\Services;

use App\Constants\Status;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Modules\ProductImport\Data\ProductImportPreview;
use App\Modules\ProductImport\Data\ProductImportValidationError;
use App\Modules\ProductImport\Exceptions\ProductImportException;
use App\Modules\ProductImport\Exports\ProductImportTemplateExport;
use App\Modules\ProductImport\Imports\ProductImportRows;
use App\Services\ProductImageUrlImporter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class ProductImportService
{
    private const CACHE_PREFIX = 'product-import-preview:';

    private const LOCK_PREFIX = 'product-import-submit:';

    private const TTL_MINUTES = 60;

    private const MAX_ROWS = 1000;

    public function __construct(
        private readonly DatabaseManager $database,
    ) {}

    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new ProductImportTemplateExport, 'template-import-produk.xlsx');
    }

    public function validate(UploadedFile $file, int $userId): ProductImportPreview
    {
        $import = new ProductImportRows;

        try {
            Excel::import($import, $file);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'file' => __('admin/product-import.notifications.invalid_file'),
            ]);
        }

        $rows = $import->products->rows ?? collect();
        $errors = [];
        $validRows = [];
        $reservedCodes = [];

        if (max(0, $rows->count() - 1) > self::MAX_ROWS) {
            $errors[] = new ProductImportValidationError(0, 'file', __('admin/product-import.validation.too_many_rows', ['max' => self::MAX_ROWS]));
        }

        $categories = Category::query()->get()->groupBy(fn (Category $category): string => $this->normalize($category->name));
        $warehouses = Warehouse::query()->get()->groupBy(fn (Warehouse $warehouse): string => $this->normalize($warehouse->name));

        // Row 1 is the header. The import contract is positional: A..K map to
        // the documented product fields regardless of parsed header keys.
        foreach ($rows->skip(1)->values()->take(self::MAX_ROWS) as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->normalizeRow($row);

            if ($this->isBlankRow($data)) {
                continue;
            }

            $rowErrors = [];
            foreach (['name' => 'name', 'category' => 'category', 'digital' => 'digital', 'price' => 'price', 'stock' => 'stock'] as $field => $column) {
                if ($data[$field] === '') {
                    $rowErrors[] = new ProductImportValidationError($rowNumber, $column, __('admin/product-import.validation.required'));
                }
            }

            if ($data['name'] !== '' && mb_strlen($data['name']) > 255) {
                $rowErrors[] = new ProductImportValidationError($rowNumber, 'name', __('admin/product-import.validation.max_name'));
            }

            foreach (['price', 'sale_price'] as $field) {
                if ($data[$field] !== '' && (! is_numeric($data[$field]) || (float) $data[$field] < 0)) {
                    $rowErrors[] = new ProductImportValidationError($rowNumber, $field, __('admin/product-import.validation.numeric'));
                }
            }

            foreach (['stock', 'weight'] as $field) {
                if ($data[$field] !== '' && (! ctype_digit((string) $data[$field]) || (int) $data[$field] < 0)) {
                    $rowErrors[] = new ProductImportValidationError($rowNumber, $field, __('admin/product-import.validation.integer'));
                }
            }

            if (is_numeric($data['price']) && is_numeric($data['sale_price']) && (float) $data['sale_price'] >= (float) $data['price']) {
                $rowErrors[] = new ProductImportValidationError($rowNumber, 'sale_price', __('admin/product-import.validation.sale_price'));
            }

            $type = $this->productType($data['digital']);
            if ($data['digital'] !== '' && $type === null) {
                $rowErrors[] = new ProductImportValidationError($rowNumber, 'digital', __('admin/product-import.validation.product_type'));
            }

            if ($type === Status::PHYSICAL_PRODUCT) {
                foreach (['weight' => 'weight', 'warehouse' => 'warehouse'] as $field => $column) {
                    if ($data[$field] === '') {
                        $rowErrors[] = new ProductImportValidationError($rowNumber, $column, __('admin/product-import.validation.required_physical'));
                    }
                }
            }

            $category = $categories->get($this->normalize($data['category']))?->count() === 1
                ? $categories->get($this->normalize($data['category']))->first()
                : null;
            if ($data['category'] !== '' && $category === null) {
                $rowErrors[] = new ProductImportValidationError($rowNumber, 'category', __('admin/product-import.validation.reference_not_found'));
            }

            $warehouse = $warehouses->get($this->normalize($data['warehouse']))?->count() === 1
                ? $warehouses->get($this->normalize($data['warehouse']))->first()
                : null;
            if ($data['warehouse'] !== '' && $warehouse === null) {
                $rowErrors[] = new ProductImportValidationError($rowNumber, 'warehouse', __('admin/product-import.validation.reference_not_found'));
            }

            $imageScheme = strtolower((string) parse_url($data['image_url'], PHP_URL_SCHEME));
            if ($data['image_url'] !== '' && (! filter_var($data['image_url'], FILTER_VALIDATE_URL) || ! in_array($imageScheme, ['http', 'https'], true))) {
                $rowErrors[] = new ProductImportValidationError($rowNumber, 'image_url', __('admin/product-import.validation.url'));
            }

            if ($rowErrors === []) {
                $code = $data['code'] !== '' ? $data['code'] : $this->generateUniqueCode($reservedCodes);
                $reservedCodes[] = $code;
                $validRows[] = [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'code' => $code,
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'digital' => $type,
                    'price' => (float) $data['price'],
                    'sale_price' => $data['sale_price'] === '' ? null : (float) $data['sale_price'],
                    'stock' => (int) $data['stock'],
                    'weight' => $data['weight'] === '' ? null : (int) $data['weight'],
                    'warehouse_id' => $warehouse?->id,
                    'warehouse_name' => $warehouse?->name,
                    'image_url' => $data['image_url'],
                ];
            }

            $errors = [...$errors, ...$rowErrors];
        }

        if ($errors !== [] || $validRows === []) {
            return new ProductImportPreview($validRows, $errors, null, null);
        }

        $token = (string) Str::uuid();
        $expiresAt = CarbonImmutable::now()->addMinutes(self::TTL_MINUTES);
        Cache::put($this->cacheKey($token), [
            'user_id' => $userId,
            'rows' => $validRows,
            'expires_at' => $expiresAt->toIso8601String(),
        ], $expiresAt);

        return new ProductImportPreview($validRows, [], $token, $expiresAt);
    }

    public function submit(string $token, int $userId): int
    {
        $lock = Cache::lock($this->lockKey($token), 30);

        try {
            return $lock->block(10, function () use ($token, $userId): int {
                $payload = Cache::get($this->cacheKey($token));
                if (! is_array($payload) || (int) ($payload['user_id'] ?? 0) !== $userId) {
                    throw ValidationException::withMessages(['token' => __('admin/product-import.notifications.invalid_token')]);
                }

                $count = $this->database->transaction(function () use ($payload): int {
                    $created = 0;
                    foreach ($payload['rows'] as $row) {
                        $imageUrl = $row['image_url'] ?? '';
                        unset($row['image_url'], $row['category_name'], $row['warehouse_name']);
                        $product = Product::create([...$row, 'user_id' => (int) $payload['user_id']]);
                        if ($imageUrl !== '') {
                            app(ProductImageUrlImporter::class)->import($product, [['url' => $imageUrl]]);
                        }
                        $created++;
                    }

                    return $created;
                });

                Cache::forget($this->cacheKey($token));

                return $count;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($exception instanceof LockTimeoutException) {
                throw ValidationException::withMessages(['token' => __('admin/product-import.notifications.busy')]);
            }

            throw new ProductImportException(__('admin/product-import.notifications.submit_failed'), 0, $exception);
        } finally {
            optional($lock)->release();
        }
    }

    private function cacheKey(string $token): string
    {
        return self::CACHE_PREFIX.$token;
    }

    private function lockKey(string $token): string
    {
        return self::LOCK_PREFIX.$token;
    }

    private function normalize(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /** @return array<string,string> */
    private function normalizeRow(mixed $row): array
    {
        $values = array_values(is_array($row) ? $row : $row->toArray());

        // Column position is the source of truth. Header labels are intentionally
        // ignored so Excel key formatting cannot change the import contract.
        $row = [];
        foreach (['nama_produk', 'deskripsi', 'kode_produk', 'kategori', 'tipe_produk', 'harga', 'harga_diskon', 'stok', 'berat', 'gudang_pengiriman', 'gambar_url'] as $index => $key) {
            $row[$key] = $values[$index] ?? null;
        }

        $get = function (string $key) use ($row): string {
            $value = $row[$key] ?? null;

            return $value === null ? '' : trim((string) $value);
        };

        return [
            'name' => $get('nama_produk'),
            'description' => $get('deskripsi'),
            'code' => $get('kode_produk'),
            'category' => $get('kategori'),
            'digital' => $get('tipe_produk'),
            'price' => $get('harga'),
            'sale_price' => $get('harga_diskon'),
            'stock' => $get('stok'),
            'weight' => $get('berat'),
            'warehouse' => $get('gudang_pengiriman'),
            'image_url' => $get('gambar_url'),
        ];
    }

    /** @param array<string,string> $data */
    private function isBlankRow(array $data): bool
    {
        return collect($data)->every(fn (string $value): bool => $value === '');
    }

    private function productType(string $value): ?int
    {
        return match ($this->normalize($value)) {
            'fisik', 'physical', 'produk fisik' => Status::PHYSICAL_PRODUCT,
            'digital', 'produk digital' => Status::DIGITAL_PRODUCT,
            default => null,
        };
    }

    /** @param array<int,string> $reservedCodes */
    private function generateUniqueCode(array $reservedCodes = []): string
    {
        do {
            $code = ProductResource::generateProductCode();
        } while (Product::query()->where('code', $code)->exists() || in_array($code, $reservedCodes, true));

        return $code;
    }
}
