<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $type = fake()->randomElement([Client::TYPE_INDIVIDUAL, Client::TYPE_COMPANY]);

        return [
            'type' => $type,
            'first_name' => $type === Client::TYPE_INDIVIDUAL ? fake()->firstName() : null,
            'last_name' => $type === Client::TYPE_INDIVIDUAL ? fake()->lastName() : null,
            'company_name' => $type === Client::TYPE_COMPANY ? fake()->company() : null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_number' => $type === Client::TYPE_COMPANY ? 'TVA-'.fake()->numerify('######') : null,
            'registration_number' => $type === Client::TYPE_COMPANY ? 'RC-'.fake()->numerify('######') : null,
            'address_line' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Lagos', 'Abuja', 'Ibadan', 'Kano']),
            'country' => 'Nigeria',
            'is_blacklisted' => false,
            'blacklist_reason' => null,
            'notes' => fake()->sentence(),
        ];
    }

    public function blacklisted(): static
    {
        return $this->state(fn () => [
            'is_blacklisted' => true,
            'blacklist_reason' => 'Incident de paiement et sinistre non régularisé.',
        ]);
    }
}
