<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('unpaid','packed','in_transit','shipped','delivered','picked_up','rejected','cancelled','completed') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('unpaid','packed','shipped','delivered','rejected','completed') NOT NULL DEFAULT 'unpaid'");
    }
};
