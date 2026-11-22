<?php

namespace Database\Factories;

use App\Models\settings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<settings>
 */
class SettingsFactory extends Factory
{
    protected $model = settings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => 'general',
            'key' => fake()->unique()->slug(2),
            'value' => fake()->sentence(),
            'type' => 'string',
            'is_public' => true,
        ];
    }
}
