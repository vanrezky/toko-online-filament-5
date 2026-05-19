<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('uuid');
            $table->date('billing_due_date')->nullable()->after('payment_type');
            $table->enum('billing_status', ['not_applicable', 'pending', 'submitted', 'paid', 'failed'])
                ->default('not_applicable')
                ->after('billing_due_date');

            $table->index('billing_due_date');
            $table->index('billing_status');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('uuid');
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->date('billing_month')->nullable()->after('due_date');
            $table->enum('collection_method', ['payroll_deduction', 'manual', 'transfer'])->nullable()->after('payment_method');
            $table->enum('payroll_status', ['scheduled', 'batched', 'submitted', 'confirmed_paid', 'failed'])
                ->default('scheduled')
                ->after('collection_method');
            $table->string('payroll_batch_reference')->nullable()->after('payroll_status');
            $table->timestamp('submitted_at')->nullable()->after('payroll_batch_reference');
            $table->timestamp('confirmed_at')->nullable()->after('submitted_at');

            $table->index('billing_month');
            $table->index('payroll_status');
        });
    }

    public function down(): void
    {
        Schema::table('installment_payments', function (Blueprint $table) {
            $table->dropIndex(['billing_month']);
            $table->dropIndex(['payroll_status']);

            $table->dropColumn([
                'code',
                'billing_month',
                'collection_method',
                'payroll_status',
                'payroll_batch_reference',
                'submitted_at',
                'confirmed_at',
            ]);
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['billing_due_date']);
            $table->dropIndex(['billing_status']);

            $table->dropColumn([
                'code',
                'billing_due_date',
                'billing_status',
            ]);
        });
    }
};
