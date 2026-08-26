<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientDocument>
 */
class ClientDocumentFactory extends Factory
{
    protected $model = ClientDocument::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'document_type' => fake()->randomElement(['driver_license', 'identity_card', 'passport']),
            'title' => fake()->randomElement(['Permis de conduire', 'Carte nationale', 'Passeport']),
            'file_path' => 'documents/clients/'.fake()->uuid().'.pdf',
            'document_number' => strtoupper(fake()->bothify('DOC-####??')),
            'expires_at' => fake()->dateTimeBetween('+2 months', '+3 years'),
        ];
    }
}
