<?php

namespace App\Modules\TransactionReceipt\Services;

use App\Models\Transaction;
use App\Modules\TransactionReceipt\Data\BatchReceiptResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

final class TransactionReceiptBatchService
{
    public function __construct(
        private readonly TransactionReceiptService $receiptService,
    ) {}

    /**
     * @param  Collection<int, Transaction>  $records
     */
    public function createArchive(Collection $records): BatchReceiptResult
    {
        $transactions = $records
            ->filter(fn ($record): bool => $record instanceof Transaction)
            ->values();

        // Resolve every status before touching storage or rendering any receipt.
        $eligible = $transactions->filter(
            fn (Transaction $transaction): bool => $this->receiptService->isEligible($transaction)
        )->values();
        $skippedCount = $transactions->count() - $eligible->count();

        if ($eligible->isEmpty()) {
            return new BatchReceiptResult(null, 0, $skippedCount);
        }

        $archivePath = tempnam(sys_get_temp_dir(), 'transaction-receipts-');

        if ($archivePath === false) {
            throw new RuntimeException('Unable to allocate a temporary receipt archive.');
        }

        $archive = new ZipArchive;

        try {
            if ($archive->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create a receipt archive.');
            }

            $disk = Storage::disk($this->receiptService->storageDisk());

            foreach ($eligible as $transaction) {
                $path = $this->receiptService->ensure($transaction);
                $contents = $disk->get($path);

                if ($contents === null) {
                    throw new RuntimeException('Unable to read a generated receipt from storage.');
                }

                $filename = 'receipt-'.($transaction->code ?: $transaction->uuid).'.pdf';
                $archive->addFromString($filename, $contents);
            }

            $archive->close();

            return new BatchReceiptResult($archivePath, $eligible->count(), $skippedCount);
        } catch (\Throwable $exception) {
            $archive->close();
            @unlink($archivePath);

            throw $exception;
        }
    }
}
