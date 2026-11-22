<?php

namespace Database\Seeders;

use App\Models\instructors;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        instructors::factory(5)->create();
    }
}
