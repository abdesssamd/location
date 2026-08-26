<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\CarDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $carDetail = CarDetail::factory()->create();

        return [
            'reference' => 'AST-'.fake()->unique()->numerify('####'),
            'name' => $carDetail->brand.' '.$carDetail->model,
            'description' => fake()->sentence(),
            'photo_path' => 'assets/demo/'.fake()->numberBetween(1, 12).'.jpg',
            'daily_rate' => fake()->randomFloat(2, 18000, 95000),
            'status' => fake()->randomElement([
                Asset::STATUS_AVAILABLE,
                Asset::STATUS_AVAILABLE,
                Asset::STATUS_AVAILABLE,
                Asset::STATUS_RENTED,
            ]),
            'specifiable_id' => $carDetail->id,
            'specifiable_type' => CarDetail::class,
            'extra_attributes' => [
                'segment' => fake()->randomElement(['city', 'suv', 'pickup', 'premium']),
                'location' => fake()->randomElement(['Lagos', 'Abuja', 'Port Harcourt']),
            ],
        ];
    }
}
