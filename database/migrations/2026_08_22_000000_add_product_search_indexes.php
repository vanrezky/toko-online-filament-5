<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index('code', 'products_code_index');
            $table->fullText(['name', 'description', 'variant', 'sub_variant'], 'products_search_fulltext');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->fullText('name', 'categories_name_fulltext');
        });

        Schema::table('product_attributes', function (Blueprint $table): void {
            $table->fullText('name', 'product_attributes_name_fulltext');
        });

        Schema::table('product_attribute_options', function (Blueprint $table): void {
            $table->fullText('name', 'product_attribute_options_name_fulltext');
        });
    }

    public function down(): void
    {
        Schema::table('product_attribute_options', function (Blueprint $table): void {
            $table->dropFullText('product_attribute_options_name_fulltext');
        });

        Schema::table('product_attributes', function (Blueprint $table): void {
            $table->dropFullText('product_attributes_name_fulltext');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropFullText('categories_name_fulltext');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropFullText('products_search_fulltext');
            $table->dropIndex('products_code_index');
        });
    }
};
