<?php

namespace Database\Seeders;

use App\Models\admins as ModelsAdmins;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL') ?: 'admin@platform.com';
        $name = env('ADMIN_NAME') ?: 'Super Admin';
        $password = env('ADMIN_PASSWORD') ?: 'Password123#';

        ModelsAdmins::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'phone' => null,
                'image' => null,
            ]
        );
    }
}
