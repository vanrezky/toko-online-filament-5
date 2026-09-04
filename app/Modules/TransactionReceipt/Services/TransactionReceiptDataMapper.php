<?php

namespace App\Modules\TransactionReceipt\Services;

use App\Models\Courier;
use App\Models\Transaction;
use App\Modules\TransactionReceipt\Contracts\ReceiptDataMapper as ReceiptDataMapperContract;
use App\Modules\TransactionReceipt\Data\TransactionReceiptData;
use App\Settings\GeneralSettings;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Throwable;

final class TransactionReceiptDataMapper implements ReceiptDataMapperContract
{
    public function __construct(
        private readonly GeneralSettings $settings,
    ) {}

    public function map(Transaction $transaction): TransactionReceiptData
    {
        $transaction->loadMissing([
            'customer',
            'address.province',
            'address.district',
            'address.subDistrict',
            'address.village',
            'products',
            'shippingDetails.warehouse',
            'vouchers',
        ]);

        $shipping = $transaction->shippingDetails->first();
        $courier = $shipping?->courier_code
            ? Courier::query()->where('code', $shipping->courier_code)->first()
            : null;
        $orderCode = (string) ($transaction->code ?: $transaction->uuid ?: $transaction->getKey());
        $barcodeValue = (string) ($transaction->receipt_code ?: $orderCode);

        return new TransactionReceiptData(
            storeName: (string) ($this->settings->site_name ?: config('app.name')),
            storeLogo: $this->storageImage($this->settings->logo),
            storeAddress: $this->nullableText($this->settings->address),
            storePhone: $this->nullableText($this->settings->phone ?: $this->settings->wa_phone),
            storeEmail: $this->nullableText($this->settings->email),
            receiverName: (string) ($transaction->customer?->full_name ?: '-'),
            receiverPhone: $this->nullableText($transaction->address?->phone ?: $transaction->customer?->phone),
            receiverAddress: $this->address($transaction),
            orderCode: $orderCode,
            trackingCode: $this->nullableText($transaction->receipt_code),
            orderDate: optional($transaction->created_at)->format('d M Y H:i') ?: '-',
            courierName: (string) ($shipping?->courier_name ?: $shipping?->courier_code ?: 'Pickup'),
            courierLogo: $this->publicImage($courier?->logo),
            warehouseName: (string) ($shipping?->warehouse?->name ?: '-'),
            weight: number_format((float) ($transaction->weight ?: $shipping?->weight ?: 0), 0, ',', '.').' gram',
            delivery: (string) ($shipping?->estimation ?: '-'),
            cod: $transaction->cod ? $this->money($transaction->total_amount) : 'Non-COD',
            buyerNote: $this->nullableText($transaction->notes) ?: '-',
            sellerNote: '-',
            products: $transaction->products->map(fn ($product): array => [
                'name' => (string) $product->display_product_name,
                'code' => (string) ($product->product_code ?: ($product->product_snapshot['product_code'] ?? '-')),
                'variation' => (string) ($product->display_variant_name ?: '-'),
                'sku' => (string) ($product->variant_sku ?: $product->product_code ?: '-'),
                'quantity' => (string) $product->quantity,
                'amount' => $this->money($product->subtotal),
            ])->values()->all(),
            barcodeValue: $barcodeValue,
            barcodeImage: $this->barcode($barcodeValue),
            qrImage: $this->qr($barcodeValue),
        );
    }

    private function address(Transaction $transaction): string
    {
        $address = $transaction->address;

        if (! $address) {
            return '-';
        }

        return implode(', ', array_filter([
            $address->address,
            $address->village?->name,
            $address->subDistrict?->name,
            $address->district?->name,
            $address->province?->name,
            $address->postal_code,
        ])) ?: '-';
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function storageImage(?string $path): ?string
    {
        if (! $path || str_starts_with($path, 'data:')) {
            return $path ?: null;
        }

        $disk = Storage::disk(config('filesystems.upload_disk', 'public'));

        try {
            if (! $disk->exists($path)) {
                return null;
            }

            return $this->dataUri($disk->get($path), $path);
        } catch (Throwable) {
            return null;
        }
    }

    private function publicImage(?string $filename): ?string
    {
        if (! $filename) {
            return null;
        }

        $path = public_path('assets/images/courier/'.basename($filename));

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        try {
            return $this->dataUri(file_get_contents($path), $path);
        } catch (Throwable) {
            return null;
        }
    }

    private function dataUri(string|false $contents, string $path): ?string
    {
        if ($contents === false || $contents === '') {
            return null;
        }

        $mime = is_file($path) && function_exists('mime_content_type')
            ? (mime_content_type($path) ?: null)
            : null;
        $mime ??= match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function barcode(string $value): ?string
    {
        try {
            $generator = new BarcodeGeneratorPNG;
            $generator->useGd();

            return 'data:image/png;base64,'.base64_encode(
                $generator->getBarcode($value, BarcodeGenerator::TYPE_CODE_128, 2, 42)
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function qr(string $value): ?string
    {
        try {
            $options = new QROptions([
                'outputType' => QROutputInterface::MARKUP_SVG,
                'outputBase64' => true,
                'addQuietzone' => true,
                'quietzoneSize' => 2,
            ]);

            return (new QRCode($options))->render($value);
        } catch (Throwable) {
            return null;
        }
    }
}
