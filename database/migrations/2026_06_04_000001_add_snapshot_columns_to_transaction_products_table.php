<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transcation_products', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('product_id');
            $table->string('product_code')->nullable()->after('product_name');
            $table->string('variant_name')->nullable()->after('product_code');
            $table->string('variant_sku')->nullable()->after('variant_name');
            $table->unsignedInteger('weight_snapshot')->nullable()->after('variant_sku');
            $table->decimal('line_subtotal', 15, 2)->default(0)->after('discount');
            $table->json('product_snapshot')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('transcation_products', function (Blueprint $table) {
            $table->dropColumn([
                'product_name',
                'product_code',
                'variant_name',
                'variant_sku',
                'weight_snapshot',
                'line_subtotal',
                'product_snapshot',
            ]);
        });
    }
};
