<?php

namespace Database\Factories;

use App\Models\CarDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarDetail>
 */
class CarDetailFactory extends Factory
{
    protected $model = CarDetail::class;

    public function definition(): array
    {
        return [
            'license_plate' => strtoupper(fake()->bothify('??-####-??')),
            'mileage' => fake()->numberBetween(5000, 180000),
            'fuel_type' => fake()->randomElement(['petrol', 'diesel', 'hybrid', 'electric']),
            'transmission' => fake()->randomElement(['manual', 'automatic']),
            'brand' => fake()->randomElement(['Toyota', 'Peugeot', 'Hyundai', 'Kia', 'Mercedes', 'Ford']),
            'model' => fake()->randomElement(['Corolla', '208', 'Tucson', 'Sportage', 'C-Class', 'Ranger']),
            'color' => fake()->safeColorName(),
            'year' => fake()->numberBetween(2018, 2025),
            'seats' => fake()->randomElement([2, 4, 5, 7]),
        ];
    }
}
