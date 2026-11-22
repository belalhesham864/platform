<?php

namespace Database\Factories;

use App\Models\instructors;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<instructors>
 */
class InstructorFactory extends Factory
{
    protected $model = instructors::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->randomNumber(5)),
            'email' => fake()->unique()->safeEmail(),
            'Bio' => fake()->paragraph(),
            'password' => Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'portfolio_picture' => null,
            'small_description' => fake()->sentence(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birthday' => fake()->date(),
            'years_of_experience' => fake()->numberBetween(1, 20),
            'is_active' => true,
            'email_verified_at' => now(),
            'rank_id' => null,
        ];
    }
}
