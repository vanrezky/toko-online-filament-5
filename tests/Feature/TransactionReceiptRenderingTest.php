<?php

namespace Tests\Feature;

use App\Modules\TransactionReceipt\Data\TransactionReceiptData;
use App\Modules\TransactionReceipt\Services\DompdfReceiptRenderer;
use Tests\TestCase;

class TransactionReceiptRenderingTest extends TestCase
{
    public function test_ginee_receipt_renders_to_a_pdf(): void
    {
        $pdf = (new DompdfReceiptRenderer)->render(new TransactionReceiptData(
            storeName: 'Demo Store',
            storeLogo: null,
            storeAddress: 'Jl. Demo No. 1',
            storePhone: '08123456789',
            storeEmail: 'demo@example.test',
            receiverName: 'Demo Customer',
            receiverPhone: '08123456789',
            receiverAddress: 'Jl. Customer No. 2, Jakarta',
            orderCode: 'TRX-DEMO',
            trackingCode: 'TRACK-DEMO',
            orderDate: '04 Sep 2026 10:00',
            courierName: 'J&T Express',
            courierLogo: null,
            warehouseName: 'Jakarta Warehouse',
            weight: '500 gram',
            delivery: '2-3 hari',
            cod: 'Non-COD',
            buyerNote: 'Please handle with care',
            sellerNote: '-',
            products: [[
                'name' => 'Demo Product',
                'code' => 'DEMO-001',
                'variation' => 'Default',
                'sku' => 'SKU-DEMO',
                'quantity' => '1',
                'amount' => 'Rp 100.000',
            ]],
            barcodeValue: 'TRACK-DEMO',
            barcodeImage: null,
            qrImage: null,
        ));

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }
}
