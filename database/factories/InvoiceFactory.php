<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 30000, 300000);
        $optionsTotal = fake()->randomFloat(2, 0, 40000);
        $latePenaltyTotal = fake()->randomFloat(2, 0, 15000);
        $damageFeeTotal = fake()->randomFloat(2, 0, 20000);
        $totalAmount = $subtotal + $optionsTotal + $latePenaltyTotal + $damageFeeTotal;
        $amountPaid = fake()->randomFloat(2, 0, $totalAmount);

        return [
            'contract_id' => Contract::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'issued_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'due_at' => fake()->dateTimeBetween('now', '+15 days'),
            'subtotal' => $subtotal,
            'options_total' => $optionsTotal,
            'late_penalty_total' => $latePenaltyTotal,
            'damage_fee_total' => $damageFeeTotal,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'status' => $amountPaid >= $totalAmount ? Invoice::STATUS_PAID : ($amountPaid > 0 ? Invoice::STATUS_PARTIALLY_PAID : Invoice::STATUS_UNPAID),
        ];
    }
}
