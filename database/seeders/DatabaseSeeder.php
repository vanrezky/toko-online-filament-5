<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
            UserSeeder::class,
            CountrySeeder::class,
            ProvinceSeeder::class,
            DistrictSeeder::class,
            SubDistrictSeeder::class,
            WarehouseSeeder::class,
            CategorySeeder::class,
            SliderSeeder::class,
            ProductSeeder::class,
            ProductReviewSeeder::class,
            VoucherSeeder::class,
            CustomerSeeder::class,
            ResellerSeeder::class,
            PageSeeder::class,
            FaqSeeder::class,
            BlogCategorySeeder::class,
            BlogPostSeeder::class,
            ProductAttributeSeeder::class,
            FlashsaleSeeder::class,
            FlashsaleSeeder::class,
            CourierSeeder::class,
            TemplateSeeder::class,
            CurrencySeeder::class,
            EmailTemplateSeeder::class,
            ContactMessageSeeder::class,
            CustomerLevelSeeder::class,
            InstallmentPlanSeeder::class,
        ]);
    }
}
