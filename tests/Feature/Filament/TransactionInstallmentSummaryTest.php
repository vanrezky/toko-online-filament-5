<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;

class TransactionInstallmentSummaryTest extends TestCase
{
    public function test_transaction_detail_declares_an_installment_summary_with_a_drill_down_link(): void
    {
        $source = file_get_contents(getcwd().'/app/Filament/Resources/Transactions/Pages/ViewTransaction.php');

        $this->assertStringContainsString("Section::make('Informasi Cicilan')", $source);
        $this->assertStringContainsString("TextEntry::make('installment.monthly_amount')", $source);
        $this->assertStringContainsString("InstallmentResource::getUrl('view'", $source);
        $this->assertStringContainsString('$record->payment_type === \'installment\'', $source);
    }
}
