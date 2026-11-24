<?php

namespace Database\Factories;

use App\Models\categories;
use App\Models\courses;
use App\Models\instructors;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<courses>
 */
class CourseFactory extends Factory
{
    protected $model = courses::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => categories::factory(),
            'instructor_id' => instructors::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 50, 500),
            'price_after_discount' => null,
            'language' => 'ar',
            'duration' => fake()->randomFloat(1, 1, 50),
            'thumbnail' => null,
            'level' => 'beginner',
            'number_of_students' => 0,
            'number_of_lectures' => 5,
            'is_published' => true,
        ];
    }
}
