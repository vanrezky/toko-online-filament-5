<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('customer_level_id')->nullable()->after('reseller_id')
                ->constrained('customer_levels')->nullOnDelete();
            $table->decimal('credit_limit', 12, 2)->nullable()->after('balance')
                ->comment('Override default credit_limit dari level, NULL = pakai default');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['customer_level_id']);
            $table->dropColumn(['customer_level_id', 'credit_limit']);
        });
    }
};