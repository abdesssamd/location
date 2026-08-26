<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('-3 months', '+3 days');
        $expectedEndAt = (clone $startAt)->modify('+'.fake()->numberBetween(1, 10).' days');
        $dailyRate = fake()->randomFloat(2, 18000, 95000);
        $rentalDays = max((int) ceil(((strtotime($expectedEndAt->format('Y-m-d H:i:s')) - strtotime($startAt->format('Y-m-d H:i:s'))) / 86400)), 1);
        $optionsTotal = fake()->randomFloat(2, 0, 35000);
        $subtotal = $dailyRate * $rentalDays;

        return [
            'contract_number' => 'CTR-'.fake()->unique()->numerify('#####'),
            'client_id' => Client::factory(),
            'asset_id' => Asset::factory(),
            'status' => fake()->randomElement([
                Contract::STATUS_RESERVED,
                Contract::STATUS_ACTIVE,
                Contract::STATUS_COMPLETED,
            ]),
            'start_at' => $startAt,
            'expected_end_at' => $expectedEndAt,
            'actual_end_at' => null,
            'rental_days' => $rentalDays,
            'daily_rate' => $dailyRate,
            'subtotal' => $subtotal,
            'options_total' => $optionsTotal,
            'late_penalty_total' => 0,
            'damage_fee_total' => 0,
            'total_amount' => $subtotal + $optionsTotal,
            'deposit_amount' => fake()->randomFloat(2, 20000, 150000),
            'deposit_retained_amount' => 0,
            'deposit_method' => fake()->randomElement(['card_hold', 'cash', 'check']),
            'deposit_status' => fake()->randomElement([
                Contract::DEPOSIT_PENDING,
                Contract::DEPOSIT_SECURED,
            ]),
            'initial_condition' => [
                'mileage' => fake()->numberBetween(5000, 150000),
                'fuel_level' => fake()->randomElement(['2/8', '4/8', '6/8', '8/8']),
                'damages' => fake()->randomElement(['Aucun', 'Rayure porte avant droite', 'Pare-chocs marqué']),
            ],
            'final_condition' => null,
            'checkout_notes' => fake()->sentence(),
            'return_notes' => null,
            'deposit_retention_reason' => null,
        ];
    }
}
