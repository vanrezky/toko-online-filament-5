<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Modules\TransactionReceipt\Contracts\ReceiptDataMapper;
use App\Modules\TransactionReceipt\Contracts\ReceiptPdfRenderer;
use App\Modules\TransactionReceipt\Data\TransactionReceiptData;
use App\Modules\TransactionReceipt\Exceptions\TransactionReceiptException;
use App\Modules\TransactionReceipt\Services\TransactionReceiptBatchService;
use App\Modules\TransactionReceipt\Services\TransactionReceiptService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class TransactionReceiptServiceTest extends TestCase
{
    public function test_eligible_receipt_is_stored_and_reused_without_rendering_again(): void
    {
        Storage::fake('public');
        config(['filesystems.upload_disk' => 'public']);

        $renderCount = 0;
        $service = $this->service($renderCount);
        $transaction = $this->transaction(TransactionStatus::packed);

        $path = $service->ensure($transaction);
        $service->ensure($transaction);

        Storage::disk('public')->assertExists($path);
        $this->assertSame(1, $renderCount);
    }

    public function test_cancelled_receipt_cannot_be_generated(): void
    {
        Storage::fake('public');
        config(['filesystems.upload_disk' => 'public']);

        $renderCount = 0;
        $service = $this->service($renderCount);

        $this->expectException(TransactionReceiptException::class);
        $service->ensure($this->transaction(TransactionStatus::cancelled));

        $this->assertSame(0, $renderCount);
    }

    public function test_batch_checks_statuses_before_generating_and_skips_cancelled_records(): void
    {
        Storage::fake('public');
        config(['filesystems.upload_disk' => 'public']);

        $renderCount = 0;
        $service = $this->service($renderCount);
        $batch = new TransactionReceiptBatchService($service);

        $result = $batch->createArchive(collect([
            $this->transaction(TransactionStatus::packed, 'TRX-ELIGIBLE'),
            $this->transaction(TransactionStatus::cancelled, 'TRX-CANCELLED'),
        ]));

        $this->assertNotNull($result->archivePath);
        $this->assertSame(1, $result->eligibleCount);
        $this->assertSame(1, $result->skippedCount);
        $this->assertSame(1, $renderCount);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($result->archivePath) === true);
        $this->assertSame(1, $archive->numFiles);
        $this->assertSame('receipt-TRX-ELIGIBLE.pdf', $archive->getNameIndex(0));
        $archive->close();
        unlink($result->archivePath);
    }

    public function test_batch_with_only_cancelled_records_does_not_create_archive_or_render(): void
    {
        Storage::fake('public');
        config(['filesystems.upload_disk' => 'public']);

        $renderCount = 0;
        $service = $this->service($renderCount);
        $result = (new TransactionReceiptBatchService($service))->createArchive(collect([
            $this->transaction(TransactionStatus::cancelled),
        ]));

        $this->assertNull($result->archivePath);
        $this->assertSame(0, $result->eligibleCount);
        $this->assertSame(1, $result->skippedCount);
        $this->assertSame(0, $renderCount);
    }

    private function service(int &$renderCount): TransactionReceiptService
    {
        $mapper = new class implements ReceiptDataMapper
        {
            public function map(Transaction $transaction): TransactionReceiptData
            {
                return new TransactionReceiptData(
                    storeName: 'Test Store',
                    storeLogo: null,
                    storeAddress: null,
                    storePhone: null,
                    storeEmail: null,
                    receiverName: 'Test Customer',
                    receiverPhone: null,
                    receiverAddress: 'Test Address',
                    orderCode: (string) $transaction->code,
                    trackingCode: null,
                    orderDate: '01 Jan 2026 00:00',
                    courierName: 'Test Courier',
                    courierLogo: null,
                    warehouseName: 'Test Warehouse',
                    weight: '100 gram',
                    delivery: '-',
                    cod: 'Non-COD',
                    buyerNote: '-',
                    sellerNote: '-',
                    products: [],
                    barcodeValue: (string) $transaction->code,
                    barcodeImage: null,
                    qrImage: null,
                );
            }
        };

        $renderer = new class($renderCount) implements ReceiptPdfRenderer
        {
            public function __construct(private int &$renderCount) {}

            public function render(TransactionReceiptData $receipt): string
            {
                $this->renderCount++;

                return '%PDF-test';
            }
        };

        return new TransactionReceiptService($mapper, $renderer);
    }

    private function transaction(TransactionStatus $status, string $code = 'TRX-TEST'): Transaction
    {
        $transaction = new Transaction;
        $transaction->forceFill([
            'uuid' => fake()->uuid(),
            'code' => $code,
            'status' => $status,
        ]);

        return $transaction;
    }
}
