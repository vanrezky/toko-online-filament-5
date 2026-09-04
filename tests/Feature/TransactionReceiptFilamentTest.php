<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransactionReceiptFilamentTest extends TestCase
{
    public function test_transaction_detail_has_an_eligible_receipt_action(): void
    {
        $source = file_get_contents(getcwd() . '/app/Filament/Resources/Transactions/Pages/ViewTransaction.php');

        $this->assertStringContainsString('getReceiptActions', $source);
        $this->assertStringContainsString('printReceipt', $source);
        $this->assertStringContainsString('isEligible($record)', $source);
        $this->assertStringContainsString('TransactionReceiptService::class', $source);
    }

    public function test_transaction_list_has_a_receipt_batch_action(): void
    {
        $source = file_get_contents(getcwd() . '/app/Filament/Resources/Transactions/TransactionResource.php');

        $this->assertStringContainsString("BulkAction::make('print_receipts')", $source);
        $this->assertStringContainsString('TransactionReceiptBatchService::class', $source);
        $this->assertStringContainsString('createArchive($records)', $source);
    }
}
