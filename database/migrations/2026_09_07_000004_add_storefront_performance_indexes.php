<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['is_active', 'created_at'], 'products_is_active_created_at_index');
        });

        Schema::table('template_sections', function (Blueprint $table): void {
            $table->index(
                ['template_id', 'is_active', 'order_priority'],
                'template_sections_template_active_order_index',
            );
        });

        Schema::table('flashsales', function (Blueprint $table): void {
            $table->index(
                ['is_active', 'start_time', 'end_time'],
                'flashsales_active_start_end_index',
            );
        });

        Schema::table('vouchers', function (Blueprint $table): void {
            $table->index(
                ['is_active', 'is_public', 'start_at', 'end_at', 'created_at'],
                'vouchers_public_listing_index',
            );
        });

        Schema::table('product_flashsales', function (Blueprint $table): void {
            $table->index(
                ['product_id', 'flashsale_id'],
                'product_flashsales_product_flashsale_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_is_active_created_at_index');
        });

        Schema::table('template_sections', function (Blueprint $table): void {
            $table->dropIndex('template_sections_template_active_order_index');
        });

        Schema::table('flashsales', function (Blueprint $table): void {
            $table->dropIndex('flashsales_active_start_end_index');
        });

        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropIndex('vouchers_public_listing_index');
        });

        Schema::table('product_flashsales', function (Blueprint $table): void {
            $table->dropIndex('product_flashsales_product_flashsale_index');
        });
    }
};
