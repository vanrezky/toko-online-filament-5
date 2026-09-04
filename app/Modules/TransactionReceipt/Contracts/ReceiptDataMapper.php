<?php

namespace App\Modules\TransactionReceipt\Contracts;

use App\Models\Transaction;
use App\Modules\TransactionReceipt\Data\TransactionReceiptData;

interface ReceiptDataMapper
{
    public function map(Transaction $transaction): TransactionReceiptData;
}
