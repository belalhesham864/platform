<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('set locale middleware sets app locale to arabic on api requests', function () {
    Route::get('/api/test-locale', function () {
        return response()->json([
            'locale' => app()->getLocale(),
        ]);
    });

    $response = $this->getJson('/api/test-locale');

    $response->assertOk()
        ->assertJson([
            'locale' => 'ar',
        ]);
});

test('validation errors return arabic messages and field attributes', function () {
    $response = $this->postJson('/api/student/register', []);

    $response->assertStatus(422)
        ->assertJson([
            'status' => false,
            'message' => 'بيانات الطلب غير صالحة',
            'data' => [],
        ])
        ->assertJsonValidationErrors(['name', 'email', 'password']);

    $errors = $response->json('errors');
    expect($errors['name'][0])->toBe('حقل الاسم مطلوب.')
        ->and($errors['email'][0])->toBe('حقل البريد الإلكتروني مطلوب.')
        ->and($errors['password'][0])->toBe('حقل كلمة المرور مطلوب.');
});

test('unauthenticated api requests return arabic error message with 401 status', function () {
    $response = $this->getJson('/api/admin/profile');

    $response->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'يجب تسجيل الدخول أولاً للوصول إلى هذا المصدر',
            'data' => [],
            'errors' => null,
        ]);
});

test('not found api requests return arabic error message with 404 status', function () {
    $response = $this->getJson('/api/unknown-endpoint');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => 'المصدر المطلوب غير موجود',
            'data' => [],
            'errors' => null,
        ]);
});

test('forbidden api requests return arabic error message with 403 status', function () {
    Route::get('/api/test-forbidden', function () {
        abort(403);
    });

    $response = $this->getJson('/api/test-forbidden');

    $response->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => 'غير مصرح لك بالقيام بهذا الإجراء',
            'data' => [],
            'errors' => null,
        ]);
});

test('too many requests return arabic error message with 429 status', function () {
    Route::get('/api/test-throttle', function () {
        abort(429);
    });

    $response = $this->getJson('/api/test-throttle');

    $response->assertStatus(429)
        ->assertJson([
            'status' => false,
            'message' => 'تم تجاوز عدد المحاولات المسموح بها، يرجى المحاولة لاحقاً',
            'data' => [],
            'errors' => null,
        ]);
});

test('server error returns generic arabic error message with 500 status', function () {
    Route::get('/api/test-server-error', function () {
        abort(500);
    });

    $response = $this->getJson('/api/test-server-error');

    $response->assertStatus(500)
        ->assertJson([
            'status' => false,
            'message' => 'حدث خطأ أثناء معالجة الطلب',
            'data' => [],
            'errors' => null,
        ]);
});
