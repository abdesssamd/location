<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDocument>
 */
class AssetDocumentFactory extends Factory
{
    protected $model = AssetDocument::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'document_type' => fake()->randomElement(['insurance', 'registration', 'inspection']),
            'title' => fake()->randomElement(['Police assurance', 'Carte grise', 'Contrôle technique']),
            'file_path' => 'documents/assets/'.fake()->uuid().'.pdf',
            'expires_at' => fake()->dateTimeBetween('now', '+8 months'),
            'notes' => fake()->sentence(),
        ];
    }
}
