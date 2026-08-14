<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('customers', fn (Blueprint $table) => $table->string('timezone', 64)->nullable()->after('phone'));
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('customer_timezone', 64)->nullable()->after('timelimit');
            $table->index(['billing_status', 'status', 'timelimit'], 'transactions_expiry_index');
        });
    }
    public function down(): void {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_expiry_index');
            $table->dropColumn('customer_timezone');
        });
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('timezone'));
    }
};
