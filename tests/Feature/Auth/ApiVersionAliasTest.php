<?php

use App\Models\admins;
use App\Models\instructors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('api v1 route aliases work for all user types', function () {
    // 1. Student register on /api/v1/student/register
    $studentReg = $this->postJson('/api/v1/student/register', [
        'name' => 'V1 Student',
        'email' => 'v1student@example.com',
        'password' => 'Password123#',
        'password_confirmation' => 'Password123#',
    ])->assertCreated();

    expect($studentReg->json('data.email'))->toBe('v1student@example.com');

    // 2. Admin login on /api/v1/admin/login
    admins::factory()->create([
        'email' => 'v1admin@example.com',
        'password' => Hash::make('Password123#'),
    ]);

    $adminLogin = $this->postJson('/api/v1/admin/login', [
        'email' => 'v1admin@example.com',
        'password' => 'Password123#',
    ])->assertOk();

    expect($adminLogin->json('data.token'))->not->toBeEmpty();

    // 3. Instructor login on /api/v1/instructor/login
    instructors::factory()->create([
        'email' => 'v1instructor@example.com',
        'password' => Hash::make('Password123#'),
        'is_active' => true,
    ]);

    $instLogin = $this->postJson('/api/v1/instructor/login', [
        'email' => 'v1instructor@example.com',
        'password' => 'Password123#',
    ])->assertOk();

    expect($instLogin->json('data.token'))->not->toBeEmpty();
});
