<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Transactions\TransactionResource;
use Tests\TestCase;

class TransactionPaymentTypeLabelTest extends TestCase
{
    public function test_balance_payment_type_has_a_store_balance_label(): void
    {
        $this->assertSame('Saldo Toko', TransactionResource::getPaymentTypeLabel('balance'));
    }

    public function test_credit_limit_payment_types_keep_their_distinct_labels(): void
    {
        $this->assertSame('Bayar Penuh dari Limit Kredit', TransactionResource::getPaymentTypeLabel('full'));
        $this->assertSame('Cicilan', TransactionResource::getPaymentTypeLabel('installment'));
    }
}
