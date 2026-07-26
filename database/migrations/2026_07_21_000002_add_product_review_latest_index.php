<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('product_reviews', 'product_reviews_product_created_index')) {
            return;
        }

        Schema::table('product_reviews', fn (Blueprint $table) => $table->index(['product_id', 'created_at'], 'product_reviews_product_created_index'));
    }

    public function down(): void
    {
        if (! Schema::hasIndex('product_reviews', 'product_reviews_product_created_index')) {
            return;
        }

        Schema::table('product_reviews', fn (Blueprint $table) => $table->dropIndex('product_reviews_product_created_index'));
    }
};
