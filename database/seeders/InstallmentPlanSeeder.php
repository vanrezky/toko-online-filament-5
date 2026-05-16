<?php

namespace Database\Seeders;

use App\Models\InstallmentPlan;
use Illuminate\Database\Seeder;

class InstallmentPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['tenor' => 3, 'fee_percentage' => 2.00, 'description' => 'Cicilan 3 bulan dengan fee 2%', 'is_active' => true],
            ['tenor' => 6, 'fee_percentage' => 5.00, 'description' => 'Cicilan 6 bulan dengan fee 5%', 'is_active' => true],
            ['tenor' => 12, 'fee_percentage' => 10.00, 'description' => 'Cicilan 12 bulan dengan fee 10%', 'is_active' => true],
            ['tenor' => 24, 'fee_percentage' => 20.00, 'description' => 'Cicilan 24 bulan dengan fee 20%', 'is_active' => true],
        ];

        foreach ($plans as $plan) {
            InstallmentPlan::updateOrCreate(
                ['tenor' => $plan['tenor']],
                $plan
            );
        }
    }
}