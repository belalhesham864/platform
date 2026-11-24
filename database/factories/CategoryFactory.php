<?php

namespace Database\Factories;

use App\Models\categories;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<categories>
 */
class CategoryFactory extends Factory
{
    protected $model = categories::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => fake()->unique()->slug(2),
            'small_description' => fake()->sentence(),
            'icon' => fake()->uuid().'.png',
        ];
    }
}
