<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY billing_status ENUM('not_applicable','pending','submitted','paid','failed','cancelled') NOT NULL DEFAULT 'not_applicable'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY billing_status ENUM('not_applicable','pending','submitted','paid','failed') NOT NULL DEFAULT 'not_applicable'");
    }
};
