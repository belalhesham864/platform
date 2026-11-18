<?php

use App\Mail\SendOtpMail;
use App\Models\User;
use Ichtrojan\Otp\Models\Otp as OtpModel;
use Ichtrojan\Otp\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('student can register normally and returns student resource', function () {
    $response = $this->postJson('/api/student/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123#',
        'password_confirmation' => 'Password123#',
    ]);

    $response->assertCreated()
        ->assertJson([
            'status' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'errors' => null,
        ])
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'created_at',
            ],
            'errors',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

test('student registration fails if email already exists', function () {
    User::factory()->create([
        'email' => 'john@example.com',
    ]);

    $response = $this->postJson('/api/student/register', [
        'name' => 'Another John',
        'email' => 'john@example.com',
        'password' => 'Password123#',
        'password_confirmation' => 'Password123#',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => false,
            'message' => 'بيانات الطلب غير صالحة',
            'errors' => [
                'email' => ['البريد الإلكتروني مستخدم بالفعل.'],
            ],
        ]);
});

test('student can login with valid credentials', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('Password123#'),
    ]);

    $response = $this->postJson('/api/student/login', [
        'email' => 'john@example.com',
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
                'student' => ['id', 'name', 'email', 'created_at'],
                'token',
                'token_type',
            ],
            'errors',
        ]);
});

test('student cannot login with invalid credentials or non-existent email', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('Password123#'),
    ]);

    // Wrong password
    $this->postJson('/api/student/login', [
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
            'data' => [],
            'errors' => null,
        ]);

    // Non-existent email
    $this->postJson('/api/student/login', [
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

test('authenticated student can retrieve profile and logout', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('Password123#'),
    ]);

    $loginResponse = $this->postJson('/api/student/login', [
        'email' => 'john@example.com',
        'password' => 'Password123#',
    ])->assertOk();

    $token = $loginResponse->json('data.token');

    // Profile
    $this->withToken($token)->getJson('/api/student/profile')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم استرجاع بيانات الملف الشخصي بنجاح',
            'data' => [
                'email' => 'john@example.com',
            ],
        ]);

    // Logout
    $this->withToken($token)->postJson('/api/student/logout')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);

    // Profile after logout
    $this->withToken($token)->getJson('/api/student/profile')->assertUnauthorized();
});

test('student can request password reset otp', function () {
    Mail::fake();

    User::factory()->create([
        'email' => 'john@example.com',
    ]);

    $response = $this->postJson('/api/student/forgot-password', [
        'email' => 'john@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني بنجاح',
        ]);

    Mail::assertSent(SendOtpMail::class, function ($mail) {
        return $mail->hasTo('john@example.com');
    });
});

test('student can reset password using otp', function () {
    $student = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('OldPassword123'),
    ]);

    (new Otp)->generate('john@example.com', 'numeric', 6, 10);
    $otpRecord = OtpModel::where('identifier', 'john@example.com')->latest()->first();

    $response = $this->postJson('/api/student/reset-password', [
        'email' => 'john@example.com',
        'otp' => $otpRecord->token,
        'password' => 'NewPassword123#',
        'password_confirmation' => 'NewPassword123#',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
        ]);

    $student->refresh();
    expect(Hash::check('NewPassword123#', $student->password))->toBeTrue();
});
