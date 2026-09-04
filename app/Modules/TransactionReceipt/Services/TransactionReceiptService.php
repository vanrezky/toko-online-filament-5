<?php

namespace App\Modules\TransactionReceipt\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Modules\TransactionReceipt\Contracts\ReceiptDataMapper;
use App\Modules\TransactionReceipt\Contracts\ReceiptPdfRenderer;
use App\Modules\TransactionReceipt\Exceptions\TransactionReceiptException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TransactionReceiptService
{
    private const STORAGE_DIRECTORY = 'receipts/transactions';

    public function __construct(
        private readonly ReceiptDataMapper $dataMapper,
        private readonly ReceiptPdfRenderer $renderer,
    ) {}

    public function isEligible(Transaction $transaction): bool
    {
        $status = $transaction->status;

        if ($status instanceof TransactionStatus) {
            return $status !== TransactionStatus::cancelled;
        }

        return TransactionStatus::tryFrom((string) $status) !== TransactionStatus::cancelled;
    }

    public function storageDisk(): string
    {
        return (string) config('filesystems.upload_disk', 'public');
    }

    public function storagePath(Transaction $transaction): string
    {
        $identity = (string) ($transaction->uuid ?: $transaction->getKey());

        return self::STORAGE_DIRECTORY.'/'.$identity.'.pdf';
    }

    public function ensure(Transaction $transaction): string
    {
        if (! $this->isEligible($transaction)) {
            throw new TransactionReceiptException('Cancelled transactions cannot have a receipt.');
        }

        $disk = Storage::disk($this->storageDisk());
        $path = $this->storagePath($transaction);

        if ($disk->exists($path)) {
            return $path;
        }

        $contents = $this->renderer->render($this->dataMapper->map($transaction));

        if ($contents === '') {
            throw new TransactionReceiptException('Receipt rendering returned an empty PDF.');
        }

        if (! $disk->put($path, $contents)) {
            throw new TransactionReceiptException('Receipt could not be saved to storage.');
        }

        return $path;
    }

    public function download(Transaction $transaction): StreamedResponse|Response
    {
        $path = $this->ensure($transaction);
        $filename = 'receipt-'.Str::slug((string) ($transaction->code ?: $transaction->uuid), '-').'.pdf';

        return Storage::disk($this->storageDisk())->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
