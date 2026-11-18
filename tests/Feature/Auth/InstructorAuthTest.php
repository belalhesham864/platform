<?php

use App\Mail\SendOtpMail;
use App\Models\instructors;
use Ichtrojan\Otp\Models\Otp as OtpModel;
use Ichtrojan\Otp\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('instructor has no public register endpoint', function () {
    $this->postJson('/api/instructor/register', [
        'name' => 'Instructor User',
        'email' => 'instructor@example.com',
        'password' => 'password',
    ])->assertStatus(404);
});

test('instructor can login with valid credentials', function () {
    instructors::factory()->create([
        'email' => 'instructor@example.com',
        'password' => Hash::make('Password123#'),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/instructor/login', [
        'email' => 'instructor@example.com',
        'password' => 'Password123#',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'errors' => null,
        ])
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'instructor' => ['id', 'name', 'email', 'slug', 'is_active', 'created_at'],
                'token',
                'token_type',
            ],
            'errors',
        ]);
});

test('instructor cannot login with invalid credentials or non-existent email', function () {
    instructors::factory()->create([
        'email' => 'instructor@example.com',
        'password' => Hash::make('Password123#'),
        'is_active' => true,
    ]);

    // Wrong password
    $this->postJson('/api/instructor/login', [
        'email' => 'instructor@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
            'data' => [],
            'errors' => null,
        ]);

    // Non-existent email
    $this->postJson('/api/instructor/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'Password123#',
    ])->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
            'data' => [],
            'errors' => null,
        ]);
});

test('deactivated instructor cannot login', function () {
    instructors::factory()->create([
        'email' => 'inactive@example.com',
        'password' => Hash::make('Password123#'),
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/instructor/login', [
        'email' => 'inactive@example.com',
        'password' => 'Password123#',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => 'هذا الحساب معطل حالياً، يرجى التواصل مع الإدارة',
        ]);
});

test('authenticated instructor can get profile and logout', function () {
    instructors::factory()->create([
        'email' => 'instructor@example.com',
        'password' => Hash::make('Password123#'),
        'is_active' => true,
    ]);

    $loginResponse = $this->postJson('/api/instructor/login', [
        'email' => 'instructor@example.com',
        'password' => 'Password123#',
    ])->assertOk();

    $token = $loginResponse->json('data.token');

    // Profile
    $this->withToken($token)->getJson('/api/instructor/profile')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم استرجاع بيانات الملف الشخصي بنجاح',
            'data' => [
                'email' => 'instructor@example.com',
            ],
        ]);

    // Logout
    $this->withToken($token)->postJson('/api/instructor/logout')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);

    // Profile after logout
    $this->withToken($token)->getJson('/api/instructor/profile')->assertUnauthorized();
});

test('instructor can request password reset otp', function () {
    Mail::fake();

    instructors::factory()->create([
        'email' => 'instructor@example.com',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/instructor/forgot-password', [
        'email' => 'instructor@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني بنجاح',
        ]);

    Mail::assertSent(SendOtpMail::class, function ($mail) {
        return $mail->hasTo('instructor@example.com');
    });
});

test('instructor can reset password using otp', function () {
    $instructor = instructors::factory()->create([
        'email' => 'instructor@example.com',
        'password' => Hash::make('OldPassword123'),
        'is_active' => true,
    ]);

    (new Otp)->generate('instructor@example.com', 'numeric', 6, 10);
    $otpRecord = OtpModel::where('identifier', 'instructor@example.com')->latest()->first();

    $response = $this->postJson('/api/instructor/reset-password', [
        'email' => 'instructor@example.com',
        'otp' => $otpRecord->token,
        'password' => 'NewPassword123#',
        'password_confirmation' => 'NewPassword123#',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
        ]);

    $instructor->refresh();
    expect(Hash::check('NewPassword123#', $instructor->password))->toBeTrue();
});
