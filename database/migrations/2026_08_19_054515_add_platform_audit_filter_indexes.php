<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table): void {
            $table->index('created_at', 'activity_log_created_at_index');
            $table->index(['event', 'created_at'], 'activity_log_event_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table): void {
            $table->dropIndex('activity_log_created_at_index');
            $table->dropIndex('activity_log_event_created_at_index');
        });
    }
};
