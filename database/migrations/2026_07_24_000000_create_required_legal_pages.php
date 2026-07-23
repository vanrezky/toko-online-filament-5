<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('pages')->insertOrIgnore([
            [
                'title' => 'Syarat & Ketentuan',
                'slug' => 'syarat-ketentuan',
                'content' => '',
                'order' => 0,
                'is_status' => true,
                'show_in_menu' => false,
                'menu_location' => null,
                'published_at' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Kebijakan Privasi',
                'slug' => 'kebijakan-privasi',
                'content' => '',
                'order' => 0,
                'is_status' => true,
                'show_in_menu' => false,
                'menu_location' => null,
                'published_at' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        // Preserve administrator-authored legal content if migrations are rolled back.
    }
};
