<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'invoice_id' => Invoice::factory(),
            'payment_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'amount' => fake()->randomFloat(2, 10000, 150000),
            'method' => fake()->randomElement(['cash', 'bank_transfer', 'card', 'mobile_money']),
            'status' => 'completed',
            'reference' => strtoupper(fake()->bothify('PAY-#####??')),
            'notes' => fake()->sentence(),
        ];
    }
}
