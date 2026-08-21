<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tracing_spans')) {
            return;
        }

        Schema::create('tracing_spans', function (Blueprint $table): void {
            $table->id();
            $table->char('trace_id', 32);
            $table->char('span_id', 16);
            $table->char('parent_span_id', 16)->nullable();
            $table->string('name', 255);
            $table->unsignedSmallInteger('kind')->default(0);
            $table->string('status_code', 10)->nullable();
            $table->text('status_description')->nullable();
            $table->unsignedBigInteger('start_ns');
            $table->unsignedBigInteger('end_ns')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('attributes')->nullable();
            $table->json('events')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('operation', 255)->nullable();
            $table->timestamps();

            $table->unique('span_id');
            $table->index('trace_id');
            $table->index('parent_span_id');
            $table->index('correlation_id');
            $table->index(['trace_id', 'start_ns']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracing_spans');
    }
};
