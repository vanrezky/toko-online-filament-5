<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 20);
            $table->string('provider', 100);
            $table->string('type', 30);
            $table->string('method', 10)->nullable();
            $table->text('url')->nullable();
            $table->string('endpoint', 500)->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->uuid('correlation_id');
            $table->nullableMorphs('subject');
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempt')->nullable();
            $table->string('job_name')->nullable();
            $table->string('queue')->nullable();
            $table->boolean('payload_truncated')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['provider', 'status']);
            $table->index('direction');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
