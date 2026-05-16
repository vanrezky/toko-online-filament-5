<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('payment_type', ['full', 'installment'])->default('full')
                ->after('payment_method')
                ->comment('full = bayar lunas, installment = cicilan');
            $table->foreignId('installment_plan_id')->nullable()->after('payment_type')
                ->constrained('installment_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['installment_plan_id']);
            $table->dropColumn(['payment_type', 'installment_plan_id']);
        });
    }
};