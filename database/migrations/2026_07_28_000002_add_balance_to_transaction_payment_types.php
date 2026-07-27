<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY payment_type ENUM('full', 'installment', 'balance') NOT NULL DEFAULT 'full' COMMENT 'full = bayar lunas, installment = cicilan, balance = saldo'");
    }

    public function down(): void
    {
        DB::statement("UPDATE transactions SET payment_type = 'full' WHERE payment_type = 'balance'");
        DB::statement("ALTER TABLE transactions MODIFY payment_type ENUM('full', 'installment') NOT NULL DEFAULT 'full' COMMENT 'full = bayar lunas, installment = cicilan'");
    }
};
