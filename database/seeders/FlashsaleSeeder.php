<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class FlashsaleSeeder extends Seeder
{
    public function run(): void
    {
        Template::query()->updateOrCreate(
            ['code' => 'flashsale'],
            [
                'name' => 'Flash Sale',
                'description' => 'Template toggle untuk fitur flash sale frontend.',
                'is_active' => false,
                'color_scheme' => '#EF4444',
            ]
        );
    }
}
