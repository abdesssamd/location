<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractOption>
 */
class ContractOptionFactory extends Factory
{
    protected $model = ContractOption::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomFloat(2, 3000, 15000);

        return [
            'contract_id' => Contract::factory(),
            'name' => fake()->randomElement(['Siège bébé', 'GPS', 'Assurance premium', 'Chauffeur']),
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
        ];
    }
}
