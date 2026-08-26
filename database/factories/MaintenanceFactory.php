<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maintenance>
 */
class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 weeks', '+2 days');
        $expectedEndDate = (clone $startDate)->modify('+'.fake()->numberBetween(2, 12).' days');

        return [
            'asset_id' => Asset::factory(),
            'start_date' => $startDate,
            'expected_end_date' => $expectedEndDate,
            'completed_at' => null,
            'cost' => fake()->randomFloat(2, 15000, 250000),
            'status' => fake()->randomElement([Maintenance::STATUS_SCHEDULED, Maintenance::STATUS_IN_PROGRESS]),
            'description' => fake()->sentence(10),
        ];
    }
}
