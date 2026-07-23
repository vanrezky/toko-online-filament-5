<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            $table->string('eyebrow', 100)->nullable()->after('id');
            $table->string('title')->nullable()->after('eyebrow');
            $table->string('button_label', 100)->nullable()->after('target_link');
            $table->unsignedInteger('sort_order')->default(0)->after('button_label');
            $table->index(['is_active', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'start_at', 'end_at']);
            $table->dropColumn(['eyebrow', 'title', 'button_label', 'sort_order']);
        });
    }
};
