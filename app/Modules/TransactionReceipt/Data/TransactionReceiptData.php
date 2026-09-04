<?php

namespace App\Modules\TransactionReceipt\Data;

final readonly class TransactionReceiptData
{
    /**
     * @param  array<int, array<string, string>>  $products
     */
    public function __construct(
        public string $storeName,
        public ?string $storeLogo,
        public ?string $storeAddress,
        public ?string $storePhone,
        public ?string $storeEmail,
        public string $receiverName,
        public ?string $receiverPhone,
        public string $receiverAddress,
        public string $orderCode,
        public ?string $trackingCode,
        public string $orderDate,
        public string $courierName,
        public ?string $courierLogo,
        public string $warehouseName,
        public string $weight,
        public string $delivery,
        public string $cod,
        public string $buyerNote,
        public string $sellerNote,
        public array $products,
        public string $barcodeValue,
        public ?string $barcodeImage,
        public ?string $qrImage,
    ) {}
}
