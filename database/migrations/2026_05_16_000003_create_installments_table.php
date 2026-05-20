<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('installment_plan_id')->constrained('installment_plans');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('fee_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('monthly_amount', 12, 2);
            $table->unsignedInteger('tenor');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->unsignedInteger('paid_installments')->default(0);
            $table->enum('status', ['active', 'completed', 'overdue', 'defaulted'])->default('active');
            $table->date('start_date');
            $table->date('expected_end_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};