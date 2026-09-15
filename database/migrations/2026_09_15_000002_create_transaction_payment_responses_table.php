<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_payment_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('provider');
            $table->string('source');
            $table->string('payment_channel')->nullable();
            $table->json('response');
            $table->timestamps();

            $table->unique(
                ['transaction_id', 'provider', 'source'],
                'transaction_payment_responses_transaction_provider_source_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_payment_responses');
    }
};
