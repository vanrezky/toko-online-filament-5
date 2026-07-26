<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_reviews')) {
            Schema::create('product_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('transaction_product_id')->nullable()->constrained('transcation_products')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('review')->nullable();
                $table->string('reviewer_name')->nullable();
                $table->boolean('is_anonymous')->default(false);
                $table->boolean('is_admin')->default(false);
                $table->timestamps();

                $table->unique('transaction_product_id');
                $table->index(['product_id', 'is_admin']);
            });
        }

        if (! Schema::hasColumn('products', 'fake_sold_count')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('fake_sold_count')->default(0)->after('security_stock');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('fake_sold_count');
        });

        Schema::dropIfExists('product_reviews');
    }
};
