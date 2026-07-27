<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->decimal('balance', 15, 2)->default(0)->change();
        });

        Schema::table('balances', function (Blueprint $table): void {
            $table->decimal('amount', 15, 2)->default(0)->change();
            $table->decimal('charge', 15, 2)->default(0)->change();
            $table->decimal('post_balance', 15, 2)->default(0)->change();
            $table->decimal('balance_before', 15, 2)->default(0)->after('charge');
            $table->string('type', 32)->default('top_up')->after('trx_type');
            $table->foreignId('performed_by_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->after('performed_by_id')->constrained()->nullOnDelete();
            $table->index(['customer_id', 'created_at']);
            $table->unique(['transaction_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('balances', function (Blueprint $table): void {
            $table->dropUnique(['transaction_id', 'type']);
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropConstrainedForeignId('transaction_id');
            $table->dropConstrainedForeignId('performed_by_id');
            $table->dropColumn(['balance_before', 'type']);
        });
    }
};
