<?php

namespace Database\Factories;

use App\Models\SubDistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Warehouse',
            'sub_district_id' => SubDistrict::factory(),
            'province_id' => null,
            'district_id' => null,
            'village_id' => null,
            'address' => $this->faker->address(),
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->phoneNumber(),
            'postal_code' => $this->faker->postcode(),
            'courier' => 'JNE,TIKI,POS',
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
