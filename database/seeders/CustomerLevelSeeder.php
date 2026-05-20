<?php

namespace Database\Seeders;

use App\Models\CustomerLevel;
use Illuminate\Database\Seeder;

class CustomerLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Guru/Pengajar',
                'slug' => 'guru',
                'description' => 'Tenaga pendidik',
                'default_credit_limit' => 5000000,
                'is_active' => true,
            ],
            [
                'name' => 'Staf/Karyawan',
                'slug' => 'staf',
                'description' => 'Tenaga kependidikan',
                'default_credit_limit' => 3000000,
                'is_active' => true,
            ],
            [
                'name' => 'Siswa/Murid',
                'slug' => 'siswa',
                'description' => 'Peserta didik',
                'default_credit_limit' => 1000000,
                'is_active' => true,
            ],
        ];

        foreach ($levels as $level) {
            CustomerLevel::updateOrCreate(
                ['slug' => $level['slug']],
                $level
            );
        }
    }
}