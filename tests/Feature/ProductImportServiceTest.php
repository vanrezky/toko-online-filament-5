<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\ProductImport\Exports\ProductImportTemplateExport;
use App\Modules\ProductImport\Services\ProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Tests\TestCase;

class ProductImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_contains_product_and_reference_sheets(): void
    {
        $export = new ProductImportTemplateExport;

        $this->assertCount(2, $export->sheets());
        $this->assertSame('Produk', $export->sheets()[0]->title());
        $this->assertSame('Kategori dan Gudang', $export->sheets()[1]->title());
        $this->assertSame('Nama Produk*', $export->sheets()[0]->array()[0][0]);
        $this->assertSame('Harga Diskon', $export->sheets()[0]->array()[0][6]);
        $this->assertSame('Berat', $export->sheets()[0]->array()[0][8]);
        $this->assertSame('Gudang Pengiriman', $export->sheets()[0]->array()[0][9]);
        $this->assertSame(['Kategori', null, 'Gudang', null], $export->sheets()[1]->array()[0]);
        $this->assertSame(['NO', 'Nama', 'NO', 'Nama'], $export->sheets()[1]->array()[1]);
    }

    public function test_valid_file_is_mapped_and_cached_for_review(): void
    {
        $category = Category::factory()->create(['name' => 'Sepatu', 'is_active' => true]);
        $warehouse = $this->warehouse('Gudang Pekanbaru');
        $file = $this->productFile([
            ['Produk Baru', 'Deskripsi', '', $category->name, 'Fisik', 100000, 90000, 10, 500, $warehouse->name, ''],
        ]);

        $preview = app(ProductImportService::class)->validate($file, 123);

        $this->assertEmpty($preview->errors);
        $this->assertNotNull($preview->token);
        $this->assertSame($category->id, $preview->rows[0]['category_id']);
        $this->assertSame($warehouse->id, $preview->rows[0]['warehouse_id']);
        $this->assertNotEmpty($preview->rows[0]['code']);
        $this->assertSame(123, Cache::get('product-import-preview:'.$preview->token)['user_id']);
        $this->assertNotNull($preview->expiresAt);
    }

    public function test_invalid_references_and_values_return_row_column_errors(): void
    {
        $file = $this->productFile([
            ['', '', '', 'Unknown', 'Other', 'not-number', 100, 'fraction', 5, 'Unknown Warehouse', 'not-a-url'],
        ]);

        $preview = app(ProductImportService::class)->validate($file, 123);
        $columns = array_column(array_map(fn ($error) => $error->toArray(), $preview->errors), 'column');

        $this->assertNull($preview->token);
        $this->assertContains('name', $columns);
        $this->assertContains('category', $columns);
        $this->assertContains('digital', $columns);
        $this->assertContains('price', $columns);
        $this->assertContains('stock', $columns);
        $this->assertContains('warehouse', $columns);
        $this->assertContains('image_url', $columns);
    }

    public function test_header_symbols_do_not_make_filled_values_look_empty(): void
    {
        $category = Category::factory()->create(['name' => 'Buku', 'is_active' => true]);
        $warehouse = $this->warehouse('Gudang Medan');
        $file = $this->productFile([
            ['Produk Header', '', '', $category->name, 'Fisik', 100000, 90000, 2, 100, $warehouse->name, ''],
        ], ' **');

        $preview = app(ProductImportService::class)->validate($file, 123);

        $this->assertEmpty($preview->errors, json_encode(array_map(fn ($error) => $error->toArray(), $preview->errors)));
        $this->assertNotNull($preview->token);
    }

    public function test_template_column_order_recovers_unusual_header_labels(): void
    {
        $category = Category::factory()->create(['name' => 'Kebutuhan', 'is_active' => true]);
        $warehouse = $this->warehouse('Gudangku');
        $file = $this->productFile([
            ['Produk Fallback', '', '', $category->name, 'Fisik', 15000, 10000, 10, 1000, $warehouse->name, ''],
        ], '', ['Kolom 1', 'Kolom 2', 'Kolom 3', 'Kolom 4', 'Kolom 5', 'Kolom 6', 'Kolom 7', 'Kolom 8', 'Kolom 9', 'Kolom 10', 'Kolom 11']);

        $preview = app(ProductImportService::class)->validate($file, 123);

        $this->assertEmpty($preview->errors, json_encode(array_map(fn ($error) => $error->toArray(), $preview->errors)));
        $this->assertNotNull($preview->token);
    }

    public function test_submit_creates_products_from_cache_and_rejects_reuse(): void
    {
        $category = Category::factory()->create(['name' => 'Pakaian', 'is_active' => true]);
        $warehouse = $this->warehouse('Gudang Jakarta');
        $user = User::factory()->create();
        $preview = app(ProductImportService::class)->validate($this->productFile([
            ['Produk Submit', '', '', $category->name, 'Digital', 200000, '', 3, '', '', ''],
        ]), $user->id);

        $this->assertEmpty($preview->errors, json_encode(array_map(fn ($error) => $error->toArray(), $preview->errors)));
        $this->assertSame(1, app(ProductImportService::class)->submit($preview->token, $user->id));
        $this->assertDatabaseHas('products', ['name' => 'Produk Submit', 'user_id' => $user->id]);

        $this->expectException(ValidationException::class);
        app(ProductImportService::class)->submit($preview->token, $user->id);
    }

    public function test_optional_fields_are_allowed_for_digital_products(): void
    {
        $category = Category::factory()->create(['name' => 'Digital', 'is_active' => true]);
        $preview = app(ProductImportService::class)->validate($this->productFile([
            ['Produk Digital', '', '', $category->name, 'Digital', 50000, '', 2, '', '', ''],
        ]), 123);

        $this->assertEmpty($preview->errors, json_encode(array_map(fn ($error) => $error->toArray(), $preview->errors)));
        $this->assertNull($preview->rows[0]['sale_price']);
        $this->assertNull($preview->rows[0]['weight']);
        $this->assertNull($preview->rows[0]['warehouse_id']);
    }

    public function test_physical_products_require_weight_and_warehouse(): void
    {
        $category = Category::factory()->create(['name' => 'Fisik', 'is_active' => true]);
        $preview = app(ProductImportService::class)->validate($this->productFile([
            ['Produk Fisik', '', '', $category->name, 'Fisik', 50000, '', 2, '', '', ''],
        ]), 123);

        $errors = array_map(fn ($error) => $error->toArray(), $preview->errors);
        $this->assertSame(['weight', 'warehouse'], array_column($errors, 'column'));
        $this->assertTrue(collect($errors)->every(fn (array $error): bool => $error['message'] === 'Wajib diisi untuk produk fisik.'));
    }

    public function test_failed_image_import_keeps_preview_available(): void
    {
        $category = Category::factory()->create(['name' => 'Aksesoris', 'is_active' => true]);
        $warehouse = $this->warehouse('Gudang Bandung');
        $user = User::factory()->create();
        $preview = app(ProductImportService::class)->validate($this->productFile([
            ['Produk Gagal', '', '', $category->name, 'Fisik', 100000, 90000, 3, 1, $warehouse->name, 'http://127.0.0.1/image.jpg'],
        ]), $user->id);

        $this->expectException(ValidationException::class);
        try {
            app(ProductImportService::class)->submit($preview->token, $user->id);
        } finally {
            $this->assertNotNull(Cache::get('product-import-preview:'.$preview->token));
            $this->assertDatabaseMissing('products', ['name' => 'Produk Gagal']);
        }
    }

    public function test_token_is_bound_to_owner_and_expiry(): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik', 'is_active' => true]);
        $warehouse = $this->warehouse('Gudang Surabaya');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $preview = app(ProductImportService::class)->validate($this->productFile([
            ['Produk Aman', '', '', $category->name, 'Fisik', 100000, 90000, 1, 1, $warehouse->name, ''],
        ]), $owner->id);

        try {
            app(ProductImportService::class)->submit($preview->token, $otherUser->id);
            $this->fail('A token must not be usable by another user.');
        } catch (ValidationException) {
            $this->assertNotNull(Cache::get('product-import-preview:'.$preview->token));
        }

        Cache::forget('product-import-preview:'.$preview->token);

        $this->expectException(ValidationException::class);
        app(ProductImportService::class)->submit($preview->token, $owner->id);
    }

    /** @param array<int,array<int,mixed>> $rows */
    private function productFile(array $rows, string $requiredHeaderSuffix = '', ?array $headers = null): UploadedFile
    {
        $content = ExcelFacade::raw(new class($rows, $requiredHeaderSuffix, $headers) implements FromArray, WithTitle
        {
            public function __construct(private readonly array $rows, private readonly string $requiredHeaderSuffix, private readonly ?array $headers) {}

            public function title(): string
            {
                return 'Produk';
            }

            public function array(): array
            {
                $headers = $this->headers ?? ['Nama Produk*'.$this->requiredHeaderSuffix, 'Deskripsi', 'Kode Produk', 'Kategori*'.$this->requiredHeaderSuffix, 'Tipe Produk*'.$this->requiredHeaderSuffix, 'Harga*'.$this->requiredHeaderSuffix, 'Harga Diskon', 'Stok*'.$this->requiredHeaderSuffix, 'Berat', 'Gudang Pengiriman', 'Gambar URL'];

                return array_merge([$headers], $this->rows);
            }
        }, Excel::XLSX);

        return UploadedFile::fake()->createWithContent('products.xlsx', $content);
    }

    private function warehouse(string $name): Warehouse
    {
        $now = now();
        $countryId = DB::table('countries')->insertGetId(['iso' => 'ID', 'iso3' => 'IDN', 'name' => 'Indonesia', 'created_at' => $now, 'updated_at' => $now]);
        $provinceId = DB::table('provinces')->insertGetId(['country_id' => $countryId, 'name' => 'Riau', 'rajaongkir' => 'RIAU', 'created_at' => $now, 'updated_at' => $now]);
        $districtId = DB::table('districts')->insertGetId(['province_id' => $provinceId, 'type' => 'city', 'name' => 'Pekanbaru', 'rajaongkir' => 'PKU', 'created_at' => $now, 'updated_at' => $now]);
        $subDistrictId = DB::table('sub_districts')->insertGetId(['district_id' => $districtId, 'name' => 'Sukajadi', 'rajaongkir' => '123', 'postal_code' => '28121', 'created_at' => $now, 'updated_at' => $now]);

        return Warehouse::create([
            'name' => $name,
            'sub_district_id' => $subDistrictId,
            'address' => 'Jl. Test',
            'contact_name' => 'Test User',
            'contact_phone' => '08123456789',
            'description' => 'Test warehouse',
            'is_active' => true,
        ]);
    }
}
