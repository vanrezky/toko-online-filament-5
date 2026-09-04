<?php

namespace App\Modules\TransactionReceipt\Contracts;

use App\Modules\TransactionReceipt\Data\TransactionReceiptData;

interface ReceiptPdfRenderer
{
    public function render(TransactionReceiptData $receipt): string;
}
