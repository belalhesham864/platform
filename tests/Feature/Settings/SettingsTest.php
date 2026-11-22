<?php

use App\Models\admins;
use App\Models\instructors;
use App\Models\settings;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

function createAdmin(): string
{
    $admin = admins::factory()->create([
        'email' => 'admin@platform.com',
        'password' => Hash::make('Password123#'),
    ]);

    return $admin->createToken('admin_token')->plainTextToken;
}

function createInstructor(): string
{
    $instructor = instructors::factory()->create([
        'email' => 'instructor@platform.com',
        'password' => Hash::make('Password123#'),
        'is_active' => true,
    ]);

    return $instructor->createToken('instructor_token')->plainTextToken;
}

function createStudent(): string
{
    $student = User::factory()->create([
        'email' => 'student@platform.com',
        'password' => Hash::make('Password123#'),
    ]);

    return $student->createToken('student_token')->plainTextToken;
}

test('1. admin can list all settings (public and private) and responses are cached', function () {
    $token = createAdmin();
    $this->seed(SettingsSeeder::class);

    $response = $this->withToken($token)->getJson('/api/admin/settings');

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم استرجاع الإعدادات بنجاح',
            'errors' => null,
        ])
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => ['id', 'group', 'key', 'value', 'type', 'is_public', 'created_at', 'updated_at'],
            ],
            'errors',
        ]);

    expect(count($response->json('data')))->toBe(9);
    expect(Cache::has('settings.all'))->toBeTrue();
});

test('2. admin can show a specific setting by id or key', function () {
    $token = createAdmin();
    $setting = settings::factory()->create([
        'key' => 'currency',
        'value' => 'USD',
        'is_public' => false,
    ]);

    // Show by numeric id
    $responseById = $this->withToken($token)->getJson("/api/admin/settings/{$setting->id}");
    $responseById->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم استرجاع الإعداد بنجاح',
            'data' => [
                'id' => $setting->id,
                'key' => 'currency',
                'value' => 'USD',
                'is_public' => false,
            ],
        ]);

    // Show by unique key string
    $responseByKey = $this->withToken($token)->getJson('/api/admin/settings/currency');
    $responseByKey->assertOk()
        ->assertJson([
            'status' => true,
            'data' => [
                'id' => $setting->id,
                'key' => 'currency',
            ],
        ]);
});

test('admin receives 404 when showing non-existent setting', function () {
    $token = createAdmin();

    $response = $this->withToken($token)->getJson('/api/admin/settings/99999');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => 'الإعداد المطلوب غير موجود',
            'data' => [],
        ]);
});

test('3. admin can create a setting with defaults and cache is invalidated', function () {
    $token = createAdmin();
    Cache::put('settings.all', ['cached_dummy_data'], 3600);
    Cache::put('settings.public', ['cached_public_data'], 3600);

    $response = $this->withToken($token)->postJson('/api/admin/settings', [
        'group' => 'payment',
        'key' => 'stripe_enabled',
        'value' => '1',
        'type' => 'boolean',
        'is_public' => false,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'تم إنشاء الإعداد بنجاح',
            'data' => [
                'group' => 'payment',
                'key' => 'stripe_enabled',
                'value' => true,
                'type' => 'boolean',
                'is_public' => false,
            ],
        ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'stripe_enabled',
        'group' => 'payment',
    ]);

    expect(Cache::has('settings.all'))->toBeFalse();
    expect(Cache::has('settings.public'))->toBeFalse();
});

test('admin can create setting with nullable value and database defaults', function () {
    $token = createAdmin();

    $response = $this->withToken($token)->postJson('/api/admin/settings', [
        'key' => 'optional_webhook_secret',
        'value' => null,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'data' => [
                'group' => 'general',
                'key' => 'optional_webhook_secret',
                'value' => null,
                'type' => 'string',
                'is_public' => false,
            ],
        ]);
});

test('admin cannot create a setting with duplicate key', function () {
    $token = createAdmin();
    settings::factory()->create(['key' => 'existing_key']);

    $response = $this->withToken($token)->postJson('/api/admin/settings', [
        'key' => 'existing_key',
        'value' => 'some value',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['key']);
});

test('4. admin can perform partial update on setting and cache is invalidated', function () {
    $token = createAdmin();
    $setting = settings::factory()->create([
        'key' => 'tax_rate',
        'value' => '14',
        'type' => 'integer',
        'is_public' => true,
    ]);

    Cache::put('settings.all', ['cached_dummy_data'], 3600);
    Cache::put('settings.public', ['cached_public_data'], 3600);
    Cache::put("settings.item.{$setting->id}", $setting, 3600);

    // Partial update: updating only the value
    $response = $this->withToken($token)->putJson("/api/admin/settings/{$setting->id}", [
        'value' => '15',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم تحديث الإعداد بنجاح',
            'data' => [
                'id' => $setting->id,
                'key' => 'tax_rate',
                'value' => 15,
            ],
        ]);

    $this->assertDatabaseHas('settings', [
        'id' => $setting->id,
        'value' => '15',
    ]);

    expect(Cache::has('settings.all'))->toBeFalse();
    expect(Cache::has('settings.public'))->toBeFalse();
    expect(Cache::has("settings.item.{$setting->id}"))->toBeFalse();
});

test('5. admin can delete a setting and cache is invalidated', function () {
    $token = createAdmin();
    $setting = settings::factory()->create([
        'key' => 'temporary_flag',
        'value' => 'temp',
    ]);

    Cache::put('settings.all', ['cached_dummy_data'], 3600);
    Cache::put('settings.public', ['cached_public_data'], 3600);
    Cache::put("settings.item.{$setting->id}", $setting, 3600);

    $response = $this->withToken($token)->deleteJson("/api/admin/settings/{$setting->id}");

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم حذف الإعداد بنجاح',
        ]);

    $this->assertDatabaseMissing('settings', [
        'id' => $setting->id,
    ]);

    expect(Cache::has('settings.all'))->toBeFalse();
    expect(Cache::has('settings.public'))->toBeFalse();
    expect(Cache::has("settings.item.{$setting->id}"))->toBeFalse();
});

test('6. instructor can access only public settings and cannot access full settings index or private settings', function () {
    $token = createInstructor();
    $privateSetting = settings::factory()->create([
        'key' => 'instructor_private_key',
        'value' => 'private_val',
        'is_public' => false,
    ]);
    $publicSetting = settings::factory()->create([
        'key' => 'instructor_public_key',
        'value' => 'public_val',
        'is_public' => true,
    ]);

    // 1. Instructor CAN access public settings
    $publicResponse = $this->withToken($token)->getJson('/api/instructor/settings/public');
    $publicResponse->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم استرجاع الإعدادات بنجاح',
        ])
        ->assertJsonPath("data.{$publicSetting->group}.instructor_public_key", 'public_val');

    // 2. Instructor MUST NOT access full settings index
    $this->withToken($token)->getJson('/api/instructor/settings')->assertStatus(403);
    $this->withToken($token)->getJson('/api/admin/settings')->assertStatus(403);

    // 3. Instructor MUST NOT access specific setting endpoint
    $this->withToken($token)->getJson("/api/instructor/settings/{$privateSetting->id}")->assertStatus(403);
    $this->withToken($token)->getJson("/api/admin/settings/{$privateSetting->id}")->assertStatus(403);
});

test('7. instructor cannot create, update, or delete settings', function () {
    $token = createInstructor();
    $setting = settings::factory()->create(['key' => 'some_key']);

    // Attempt create on instructor route
    $this->withToken($token)->postJson('/api/instructor/settings', [
        'key' => 'new_key',
        'value' => 'val',
    ])->assertStatus(403);

    // Attempt update on instructor route
    $this->withToken($token)->putJson("/api/instructor/settings/{$setting->id}", [
        'value' => 'updated',
    ])->assertStatus(403);

    // Attempt delete on instructor route
    $this->withToken($token)->deleteJson("/api/instructor/settings/{$setting->id}")
        ->assertStatus(403);

    // Attempt create on admin route with instructor token
    $this->withToken($token)->postJson('/api/admin/settings', [
        'key' => 'new_key',
        'value' => 'val',
    ])->assertStatus(403);
});

test('8. student can access only public settings and cannot access full settings index or private settings', function () {
    $token = createStudent();
    $privateSetting = settings::factory()->create([
        'key' => 'student_private_key',
        'value' => 'private_val',
        'is_public' => false,
    ]);
    $publicSetting = settings::factory()->create([
        'key' => 'student_public_key',
        'value' => 'public_val',
        'is_public' => true,
    ]);

    // 1. Student CAN access public settings
    $publicResponse = $this->withToken($token)->getJson('/api/student/settings/public');
    $publicResponse->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم استرجاع الإعدادات بنجاح',
        ])
        ->assertJsonPath("data.{$publicSetting->group}.student_public_key", 'public_val');

    // 2. Student MUST NOT access full settings index
    $this->withToken($token)->getJson('/api/student/settings')->assertStatus(403);
    $this->withToken($token)->getJson('/api/admin/settings')->assertStatus(403);

    // 3. Student MUST NOT access specific setting endpoint
    $this->withToken($token)->getJson("/api/student/settings/{$privateSetting->id}")->assertStatus(403);
    $this->withToken($token)->getJson("/api/admin/settings/{$privateSetting->id}")->assertStatus(403);
});

test('9. student cannot create, update, or delete settings', function () {
    $token = createStudent();
    $setting = settings::factory()->create(['key' => 'some_key']);

    // Attempt create on student route
    $this->withToken($token)->postJson('/api/student/settings', [
        'key' => 'new_key',
        'value' => 'val',
    ])->assertStatus(403);

    // Attempt update on student route
    $this->withToken($token)->putJson("/api/student/settings/{$setting->id}", [
        'value' => 'updated',
    ])->assertStatus(403);

    // Attempt delete on student route
    $this->withToken($token)->deleteJson("/api/student/settings/{$setting->id}")
        ->assertStatus(403);

    // Attempt create on admin route with student token
    $this->withToken($token)->postJson('/api/admin/settings', [
        'key' => 'new_key',
        'value' => 'val',
    ])->assertStatus(403);
});

test('10. unauthenticated requests are rejected with 401 on protected routes', function () {
    $this->getJson('/api/admin/settings')->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'يجب تسجيل الدخول أولاً للوصول إلى هذا المصدر',
        ]);

    $this->getJson('/api/instructor/settings/public')->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'يجب تسجيل الدخول أولاً للوصول إلى هذا المصدر',
        ]);

    $this->getJson('/api/student/settings/public')->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'يجب تسجيل الدخول أولاً للوصول إلى هذا المصدر',
        ]);
});

test('11. authentication is isolated between Admin, Instructor, and Student', function () {
    $adminToken = createAdmin();
    $instructorToken = createInstructor();
    $studentToken = createStudent();

    // Instructor cannot access admin routes
    $this->withToken($instructorToken)->getJson('/api/admin/settings')->assertStatus(403);
    $this->withToken($instructorToken)->getJson('/api/admin/profile')->assertStatus(403);

    // Student cannot access admin routes
    $this->withToken($studentToken)->getJson('/api/admin/settings')->assertStatus(403);
    $this->withToken($studentToken)->getJson('/api/admin/profile')->assertStatus(403);

    // Student cannot access instructor routes
    $this->withToken($studentToken)->getJson('/api/instructor/settings/public')->assertStatus(403);
    $this->withToken($studentToken)->getJson('/api/instructor/profile')->assertStatus(403);

    // Admin cannot access student routes
    $this->withToken($adminToken)->getJson('/api/student/settings/public')->assertStatus(403);
    $this->withToken($adminToken)->getJson('/api/student/profile')->assertStatus(403);

    // Instructor cannot access student routes
    $this->withToken($instructorToken)->getJson('/api/student/settings/public')->assertStatus(403);
    $this->withToken($instructorToken)->getJson('/api/student/profile')->assertStatus(403);
});

test('12. getPublicSettings groups settings by group and key and excludes private settings', function () {
    settings::create([
        'group' => 'general',
        'key' => 'site_name',
        'value' => 'My Platform',
        'type' => 'string',
        'is_public' => true,
    ]);

    settings::create([
        'group' => 'general',
        'key' => 'logo',
        'value' => 'logo.png',
        'type' => 'string',
        'is_public' => true,
    ]);

    settings::create([
        'group' => 'social',
        'key' => 'facebook',
        'value' => 'https://facebook.com',
        'type' => 'string',
        'is_public' => true,
    ]);

    // Private setting must be excluded
    settings::create([
        'group' => 'secrets',
        'key' => 'stripe_api_key',
        'value' => 'sk_live_secret123',
        'type' => 'string',
        'is_public' => false,
    ]);

    // Unauthenticated public route
    $response = $this->getJson('/api/settings/public');
    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'تم استرجاع الإعدادات بنجاح',
            'data' => [
                'general' => [
                    'site_name' => 'My Platform',
                    'logo' => 'logo.png',
                ],
                'social' => [
                    'facebook' => 'https://facebook.com',
                ],
            ],
            'errors' => null,
        ]);

    $data = $response->json('data');
    expect(isset($data['secrets']))->toBeFalse();
    expect(Cache::has('settings.public'))->toBeTrue();

    // Authenticated student public endpoint
    $studentToken = createStudent();
    $studentResponse = $this->withToken($studentToken)->getJson('/api/student/settings/public');
    $studentResponse->assertOk()
        ->assertJsonPath('data.general.site_name', 'My Platform');
    expect(isset($studentResponse->json('data')['secrets']))->toBeFalse();

    // Authenticated instructor public endpoint
    $instructorToken = createInstructor();
    $instructorResponse = $this->withToken($instructorToken)->getJson('/api/instructor/settings/public');
    $instructorResponse->assertOk()
        ->assertJsonPath('data.general.site_name', 'My Platform');
    expect(isset($instructorResponse->json('data')['secrets']))->toBeFalse();
});

test('13. value casting handles string, integer, float, boolean, and json without double encoding', function () {
    $token = createAdmin();

    $stringSetting = settings::create([
        'group' => 'app',
        'key' => 'app_name',
        'value' => 'Test App',
        'type' => 'string',
        'is_public' => true,
    ]);

    $intSetting = settings::create([
        'group' => 'app',
        'key' => 'max_items',
        'value' => '100',
        'type' => 'integer',
        'is_public' => true,
    ]);

    $floatSetting = settings::create([
        'group' => 'finance',
        'key' => 'vat_percentage',
        'value' => '14.5',
        'type' => 'float',
        'is_public' => true,
    ]);

    $boolSettingTrue = settings::create([
        'group' => 'app',
        'key' => 'is_enabled',
        'value' => 'true',
        'type' => 'boolean',
        'is_public' => true,
    ]);

    $boolSettingFalse = settings::create([
        'group' => 'app',
        'key' => 'is_maintenance',
        'value' => 'false',
        'type' => 'boolean',
        'is_public' => true,
    ]);

    $jsonSetting = settings::create([
        'group' => 'theme',
        'key' => 'palette',
        'value' => '{"primary":"#007bff","secondary":"#6c757d"}',
        'type' => 'json',
        'is_public' => true,
    ]);

    // Model accessor tests
    expect($stringSetting->value)->toBe('Test App');
    expect($intSetting->value)->toBe(100);
    expect($floatSetting->value)->toBe(14.5);
    expect($boolSettingTrue->value)->toBeTrue();
    expect($boolSettingFalse->value)->toBeFalse();
    expect($jsonSetting->value)->toBe(['primary' => '#007bff', 'secondary' => '#6c757d']);

    // API Resource tests (Admin)
    $response = $this->withToken($token)->getJson("/api/admin/settings/{$jsonSetting->id}");
    $response->assertOk()
        ->assertJson([
            'status' => true,
            'data' => [
                'id' => $jsonSetting->id,
                'key' => 'palette',
                'value' => [
                    'primary' => '#007bff',
                    'secondary' => '#6c757d',
                ],
            ],
        ]);

    // Grouped public response test
    $publicResponse = $this->getJson('/api/settings/public');
    $publicResponse->assertOk()
        ->assertJsonPath('data.theme.palette.primary', '#007bff')
        ->assertJsonPath('data.app.max_items', 100)
        ->assertJsonPath('data.finance.vat_percentage', 14.5)
        ->assertJsonPath('data.app.is_enabled', true)
        ->assertJsonPath('data.app.is_maintenance', false);
});

test('14. invalid json falls back gracefully to raw string without throwing exceptions', function () {
    $token = createAdmin();

    $corruptedSetting = settings::create([
        'group' => 'advanced',
        'key' => 'corrupted_config',
        'value' => '{"broken_json": ',
        'type' => 'json',
        'is_public' => true,
    ]);

    expect($corruptedSetting->value)->toBe('{"broken_json": ');

    $response = $this->withToken($token)->getJson("/api/admin/settings/{$corruptedSetting->id}");
    $response->assertOk()
        ->assertJsonPath('data.value', '{"broken_json": ');
});
