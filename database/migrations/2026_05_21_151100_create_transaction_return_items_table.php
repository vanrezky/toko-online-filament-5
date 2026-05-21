<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_return_id')->constrained('transaction_returns')->cascadeOnDelete();
            $table->foreignId('transaction_product_id')->constrained('transcation_products')->cascadeOnDelete();
            $table->unsignedBigInteger('qty')->default(1);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_return_items');
    }
};
