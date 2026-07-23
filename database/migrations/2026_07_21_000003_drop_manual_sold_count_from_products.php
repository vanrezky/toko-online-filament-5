<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'manual_sold_count')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn('manual_sold_count'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'manual_sold_count')) {
            Schema::table('products', fn (Blueprint $table) => $table->unsignedInteger('manual_sold_count')->default(0)->after('security_stock'));
        }
    }
};
