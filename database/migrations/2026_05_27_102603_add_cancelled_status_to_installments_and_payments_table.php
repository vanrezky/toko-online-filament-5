<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->enum('status', ['active', 'completed', 'overdue', 'defaulted', 'cancelled'])
                ->default('active')
                ->change();
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue', 'cancelled'])
                ->default('unpaid')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->enum('status', ['active', 'completed', 'overdue', 'defaulted'])
                ->default('active')
                ->change();
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue'])
                ->default('unpaid')
                ->change();
        });
    }
};
