<?php

use App\Mail\SendOtpMail;
use App\Models\admins;
use Database\Seeders\AdminSeeder;
use Ichtrojan\Otp\Models\Otp as OtpModel;
use Ichtrojan\Otp\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('admin seeder creates initial admin account', function () {
    $this->seed(AdminSeeder::class);

    $admin = admins::where('email', 'admin@platform.com')->first();

    expect($admin)->not->toBeNull()
        ->and(Hash::check('Password123#', $admin->password))->toBeTrue();
});

test('admin can login with valid credentials and receive token and unified response', function () {
    admins::factory()->create([
        'email' => 'admin@platform.com',
        'password' => Hash::make('Password123#'),
    ]);

    $response = $this->postJson('/api/admin/login', [
        'email' => 'admin@platform.com',
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
                'admin' => ['id', 'name', 'email', 'phone', 'image', 'created_at'],
                'token',
                'token_type',
            ],
            'errors',
        ]);
});

test('admin cannot login with invalid credentials or non-existent email', function () {
    admins::factory()->create([
        'email' => 'admin@platform.com',
        'password' => Hash::make('Password123#'),
    ]);

    // Wrong password
    $this->postJson('/api/admin/login', [
        'email' => 'admin@platform.com',
        'password' => 'wrong-password',
    ])->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
            'data' => [],
            'errors' => null,
        ]);

    // Email does not exist
    $this->postJson('/api/admin/login', [
        'email' => 'nonexistent@platform.com',
        'password' => 'Password123#',
    ])->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
            'data' => [],
            'errors' => null,
        ]);
});

test('authenticated admin can retrieve profile and logout', function () {
    $admin = admins::factory()->create([
        'email' => 'admin@platform.com',
        'password' => Hash::make('Password123#'),
    ]);

    $loginResponse = $this->postJson('/api/admin/login', [
        'email' => 'admin@platform.com',
        'password' => 'Password123#',
    ])->assertOk();

    $token = $loginResponse->json('data.token');

    // Retrieve Profile
    $profileResponse = $this->withToken($token)->getJson('/api/admin/profile');
    $profileResponse->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم استرجاع بيانات الملف الشخصي بنجاح',
            'data' => [
                'email' => 'admin@platform.com',
            ],
        ]);

    // Logout
    $logoutResponse = $this->withToken($token)->postJson('/api/admin/logout');
    $logoutResponse->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);

    // Profile after logout should fail
    $this->withToken($token)->getJson('/api/admin/profile')->assertUnauthorized();
});

test('admin can request password reset otp via email', function () {
    Mail::fake();

    admins::factory()->create([
        'email' => 'admin@platform.com',
    ]);

    $response = $this->postJson('/api/admin/forgot-password', [
        'email' => 'admin@platform.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني بنجاح',
        ]);

    Mail::assertSent(SendOtpMail::class, function ($mail) {
        return $mail->hasTo('admin@platform.com');
    });
});

test('admin can reset password using valid otp', function () {
    $admin = admins::factory()->create([
        'email' => 'admin@platform.com',
        'password' => Hash::make('OldPassword123'),
    ]);

    // Generate an OTP directly in the database
    (new Otp)->generate('admin@platform.com', 'numeric', 6, 10);
    $otpRecord = OtpModel::where('identifier', 'admin@platform.com')->latest()->first();

    $response = $this->postJson('/api/admin/reset-password', [
        'email' => 'admin@platform.com',
        'otp' => $otpRecord->token,
        'password' => 'NewPassword123#',
        'password_confirmation' => 'NewPassword123#',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
        ]);

    $admin->refresh();
    expect(Hash::check('NewPassword123#', $admin->password))->toBeTrue();
});
