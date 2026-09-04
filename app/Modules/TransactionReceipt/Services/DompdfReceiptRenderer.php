<?php

namespace App\Modules\TransactionReceipt\Services;

use App\Modules\TransactionReceipt\Contracts\ReceiptPdfRenderer;
use App\Modules\TransactionReceipt\Data\TransactionReceiptData;
use Dompdf\Dompdf;
use Dompdf\Options;

final class DompdfReceiptRenderer implements ReceiptPdfRenderer
{
    public function render(TransactionReceiptData $receipt): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            view('transaction-receipt.ginee', compact('receipt'))->render(),
            'UTF-8',
        );
        $dompdf->setPaper('a6', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
