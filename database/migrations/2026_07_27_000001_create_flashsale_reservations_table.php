<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flashsale_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('product_flashsale_id')->constrained('product_flashsales')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamp('released_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['transaction_id', 'product_flashsale_id'], 'fs_reservation_transaction_flashsale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashsale_reservations');
    }
};
