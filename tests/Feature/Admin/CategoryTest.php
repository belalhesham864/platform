<?php

use App\Models\admins;
use App\Models\categories;
use App\Models\courses;
use App\Models\instructors;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

if (! function_exists('categoryAdminToken')) {
    function categoryAdminToken(): string
    {
        $admin = admins::factory()->create();

        return $admin->createToken('admin_token')->plainTextToken;
    }
}

if (! function_exists('categoryInstructorToken')) {
    function categoryInstructorToken(): string
    {
        $instructor = instructors::factory()->create(['is_active' => true]);

        return $instructor->createToken('instructor_token')->plainTextToken;
    }
}

if (! function_exists('categoryStudentToken')) {
    function categoryStudentToken(): string
    {
        $student = User::factory()->create();

        return $student->createToken('student_token')->plainTextToken;
    }
}

// -------------------------------------------------------------------------
// 1. Index (List Categories)
// -------------------------------------------------------------------------
test('admin can list all categories without filters and response is cached', function () {
    $token = categoryAdminToken();
    categories::factory()->count(3)->create();

    $response = $this->withToken($token)->getJson('/api/admin/categories');

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.categories_retrieved'),
            'errors' => null,
        ])
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => ['id', 'name', 'slug', 'small_description', 'icon', 'created_at', 'updated_at'],
            ],
            'errors',
        ]);

    expect(count($response->json('data')))->toBe(3)
        ->and(Cache::has('categories.all'))->toBeTrue();
});

test('admin can search categories by name or slug without using full list cache', function () {
    $token = categoryAdminToken();

    categories::factory()->create(['name' => 'Backend Development', 'slug' => 'backend-dev']);
    categories::factory()->create(['name' => 'Frontend Development', 'slug' => 'frontend-dev']);
    categories::factory()->create(['name' => 'UI/UX Design', 'slug' => 'ui-ux-design']);

    $response = $this->withToken($token)->getJson('/api/admin/categories?search=Backend');

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.categories_retrieved'),
        ]);

    expect(count($response->json('data')))->toBe(1)
        ->and($response->json('data.0.slug'))->toBe('backend-dev')
        ->and(Cache::has('categories.all'))->toBeFalse();
});

test('admin can paginate categories using per_page query parameter', function () {
    $token = categoryAdminToken();

    categories::factory()->count(5)->create();

    $response = $this->withToken($token)->getJson('/api/admin/categories?per_page=2');

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.categories_retrieved'),
        ]);

    expect(count($response->json('data')))->toBe(2);
});

// -------------------------------------------------------------------------
// 2. Show Category
// -------------------------------------------------------------------------
test('admin can retrieve a specific category by ID and result is cached', function () {
    $token = categoryAdminToken();

    $category = categories::factory()->create([
        'name' => 'Web Development',
        'slug' => 'web-dev',
        'small_description' => 'Web development track',
        'icon' => 'web-dev.png',
    ]);

    $response = $this->withToken($token)->getJson("/api/admin/categories/{$category->id}");

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.category_retrieved'),
            'data' => [
                'id' => $category->id,
                'name' => 'Web Development',
                'slug' => 'web-dev',
                'small_description' => 'Web development track',
                'icon' => 'uploads/categories/web-dev.png',
            ],
            'errors' => null,
        ]);

    expect(Cache::has("categories.item.{$category->id}"))->toBeTrue();
});

test('admin can retrieve a specific category by slug and result is cached', function () {
    $token = categoryAdminToken();

    $category = categories::factory()->create([
        'name' => 'Mobile Apps',
        'slug' => 'mobile-apps',
        'small_description' => 'Mobile apps track',
        'icon' => 'fa-mobile',
    ]);

    $response = $this->withToken($token)->getJson('/api/admin/categories/mobile-apps');

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.category_retrieved'),
            'data' => [
                'id' => $category->id,
                'name' => 'Mobile Apps',
                'slug' => 'mobile-apps',
            ],
            'errors' => null,
        ]);

    expect(Cache::has('categories.item.mobile-apps'))->toBeTrue();
});

test('admin receives 404 when showing non-existent category by ID', function () {
    $token = categoryAdminToken();

    $response = $this->withToken($token)->getJson('/api/admin/categories/99999');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => __('api.category_not_found'),
            'errors' => null,
        ]);
});

test('admin receives 404 when showing non-existent category by slug', function () {
    $token = categoryAdminToken();

    $response = $this->withToken($token)->getJson('/api/admin/categories/non-existent-slug');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => __('api.category_not_found'),
            'errors' => null,
        ]);
});

// -------------------------------------------------------------------------
// 3. Store Category
// -------------------------------------------------------------------------
test('admin can create a category with auto-generated slug and cache is cleared', function () {
    $token = categoryAdminToken();

    Cache::put('categories.all', ['dummy']);

    $payload = [
        'name' => 'Data Science',
        'small_description' => 'Data science and machine learning courses',
        'icon' => 'data-science.png',
    ];

    $response = $this->withToken($token)->postJson('/api/admin/categories', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => __('api.category_created'),
            'data' => [
                'name' => 'Data Science',
                'small_description' => 'Data science and machine learning courses',
                'icon' => 'uploads/categories/data-science.png',
            ],
            'errors' => null,
        ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Data Science',
        'icon' => 'data-science.png',
    ]);

    $category = categories::where('name', 'Data Science')->first();
    expect($category->slug)->not->toBeEmpty()
        ->and(Cache::has('categories.all'))->toBeFalse();
});

test('admin can create a category with explicit custom slug', function () {
    $token = categoryAdminToken();

    $payload = [
        'name' => 'Cyber Security',
        'slug' => 'custom-cyber-security',
        'small_description' => 'Ethical hacking and security',
        'icon' => 'fa-shield-halved',
    ];

    $response = $this->withToken($token)->postJson('/api/admin/categories', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'data' => [
                'slug' => 'custom-cyber-security',
            ],
        ]);

    $this->assertDatabaseHas('categories', [
        'slug' => 'custom-cyber-security',
    ]);
});

test('admin can create a category with uploaded image file using ImageManger', function () {
    Storage::fake('categories');
    $token = categoryAdminToken();

    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $file = UploadedFile::fake()->createWithContent('category_icon.png', $pngContent);

    $response = $this->withToken($token)->post('/api/admin/categories', [
        'name' => 'Artificial Intelligence',
        'small_description' => 'AI and Deep Learning',
        'icon' => $file,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => __('api.category_created'),
            'data' => [
                'name' => 'Artificial Intelligence',
            ],
        ]);

    $category = categories::where('name', 'Artificial Intelligence')->first();
    expect($category->icon)->not->toBeEmpty()
        ->and(str_ends_with($category->icon, '.png'))->toBeTrue();

    Storage::disk('categories')->assertExists($category->icon);
});

test('category creation fails when required fields are missing', function () {
    $token = categoryAdminToken();

    $response = $this->withToken($token)->postJson('/api/admin/categories', []);

    $response->assertStatus(422)
        ->assertJson([
            'status' => false,
            'message' => __('api.validation_failed'),
        ])
        ->assertJsonValidationErrors(['name', 'small_description', 'icon'])
        ->assertJsonFragment(['name' => ['حقل الاسم مطلوب.']])
        ->assertJsonFragment(['small_description' => ['حقل الوصف المختصر مطلوب.']])
        ->assertJsonFragment(['icon' => ['حقل الأيقونة مطلوب.']]);
});

test('category creation fails when fields exceed maximum lengths', function () {
    $token = categoryAdminToken();

    $payload = [
        'name' => str_repeat('a', 256),
        'slug' => str_repeat('b', 256),
        'small_description' => 'Description text',
        'icon' => 'fa-icon',
    ];

    $response = $this->withToken($token)->postJson('/api/admin/categories', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'slug'])
        ->assertJsonFragment(['name' => ['يجب ألا يتجاوز الاسم 255 حرفاً.']])
        ->assertJsonFragment(['slug' => ['يجب ألا يتجاوز الرابط 255 حرفاً.']]);
});

test('category creation fails when slug is already taken', function () {
    $token = categoryAdminToken();

    categories::factory()->create(['slug' => 'unique-slug']);

    $payload = [
        'name' => 'Another Category',
        'slug' => 'unique-slug',
        'small_description' => 'Description text',
        'icon' => 'fa-icon',
    ];

    $response = $this->withToken($token)->postJson('/api/admin/categories', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['slug'])
        ->assertJsonFragment(['slug' => [__('validation.custom.slug.unique')]]);
});

// -------------------------------------------------------------------------
// 4. Update Category
// -------------------------------------------------------------------------
test('admin can update category by ID and cache is invalidated', function () {
    $token = categoryAdminToken();

    $category = categories::factory()->create([
        'name' => 'Original Name',
        'slug' => 'original-slug',
        'small_description' => 'Original description',
        'icon' => 'orig-icon.png',
    ]);

    Cache::put('categories.all', ['dummy']);
    Cache::put("categories.item.{$category->id}", $category);
    Cache::put("categories.item.{$category->slug}", $category);

    $response = $this->withToken($token)->putJson("/api/admin/categories/{$category->id}", [
        'name' => 'Updated Name',
        'small_description' => 'Updated description',
        'icon' => 'updated-icon.png',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.category_updated'),
            'data' => [
                'id' => $category->id,
                'name' => 'Updated Name',
                'small_description' => 'Updated description',
                'icon' => 'uploads/categories/updated-icon.png',
            ],
            'errors' => null,
        ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Name',
        'icon' => 'updated-icon.png',
    ]);

    expect(Cache::has('categories.all'))->toBeFalse()
        ->and(Cache::has("categories.item.{$category->id}"))->toBeFalse();
});

test('admin can update category by slug', function () {
    $token = categoryAdminToken();

    $category = categories::factory()->create([
        'slug' => 'target-slug',
    ]);

    $response = $this->withToken($token)->putJson('/api/admin/categories/target-slug', [
        'name' => 'Name After Slug Update',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.category_updated'),
            'data' => [
                'id' => $category->id,
                'name' => 'Name After Slug Update',
            ],
        ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Name After Slug Update',
    ]);
});

test('admin can update category keeping its current slug without unique conflict', function () {
    $token = categoryAdminToken();

    $category = categories::factory()->create(['slug' => 'same-slug']);

    $response = $this->withToken($token)->putJson("/api/admin/categories/{$category->id}", [
        'name' => 'Updated Category Name',
        'slug' => 'same-slug',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'data' => [
                'slug' => 'same-slug',
            ],
        ]);
});

test('admin can update category with new image file and old file is removed', function () {
    Storage::fake('categories');
    $token = categoryAdminToken();

    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $oldFile = UploadedFile::fake()->createWithContent('old_icon.png', $pngContent);
    $createResponse = $this->withToken($token)->post('/api/admin/categories', [
        'name' => 'Cloud Computing',
        'small_description' => 'Cloud tracks',
        'icon' => $oldFile,
    ], ['Accept' => 'application/json']);

    $oldIcon = $createResponse->json('data.icon');
    $oldFileName = str_replace('uploads/categories/', '', $oldIcon);
    Storage::disk('categories')->assertExists($oldFileName);

    $newFile = UploadedFile::fake()->createWithContent('new_icon.png', $pngContent);
    $updateResponse = $this->withToken($token)->post("/api/admin/categories/{$createResponse->json('data.id')}", [
        '_method' => 'PUT',
        'icon' => $newFile,
    ], ['Accept' => 'application/json']);

    $updateResponse->assertOk();
    $newIcon = $updateResponse->json('data.icon');
    $newFileName = str_replace('uploads/categories/', '', $newIcon);

    expect($newIcon)->not->toBe($oldIcon);
    Storage::disk('categories')->assertExists($newFileName);
});

test('category update fails when slug is taken by another category', function () {
    $token = categoryAdminToken();

    categories::factory()->create(['slug' => 'existing-other-slug']);
    $categoryToUpdate = categories::factory()->create(['slug' => 'current-slug']);

    $response = $this->withToken($token)->putJson("/api/admin/categories/{$categoryToUpdate->id}", [
        'slug' => 'existing-other-slug',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['slug'])
        ->assertJsonFragment(['slug' => [__('validation.custom.slug.unique')]]);
});

test('admin receives 404 when updating non-existent category', function () {
    $token = categoryAdminToken();

    $response = $this->withToken($token)->putJson('/api/admin/categories/99999', [
        'name' => 'Non Existent Name',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => __('api.category_not_found'),
            'errors' => null,
        ]);
});

// -------------------------------------------------------------------------
// 5. Delete Category
// -------------------------------------------------------------------------
test('admin can delete a category without courses and cache is invalidated', function () {
    $token = categoryAdminToken();

    $category = categories::factory()->create(['slug' => 'delete-me']);

    Cache::put('categories.all', ['dummy']);
    Cache::put("categories.item.{$category->id}", $category);
    Cache::put("categories.item.{$category->slug}", $category);

    $response = $this->withToken($token)->deleteJson("/api/admin/categories/{$category->id}");

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.category_deleted'),
            'data' => null,
            'errors' => null,
        ]);

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);

    expect(Cache::has('categories.all'))->toBeFalse()
        ->and(Cache::has("categories.item.{$category->id}"))->toBeFalse()
        ->and(Cache::has("categories.item.{$category->slug}"))->toBeFalse();
});

test('deleting a category with an uploaded image cleans up the image file', function () {
    Storage::fake('categories');
    $token = categoryAdminToken();

    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $file = UploadedFile::fake()->createWithContent('to_delete.png', $pngContent);
    $createResponse = $this->withToken($token)->post('/api/admin/categories', [
        'name' => 'DevOps',
        'small_description' => 'DevOps tracks',
        'icon' => $file,
    ], ['Accept' => 'application/json']);

    $categoryId = $createResponse->json('data.id');
    $iconPath = $createResponse->json('data.icon');
    $fileName = str_replace('uploads/categories/', '', $iconPath);
    Storage::disk('categories')->assertExists($fileName);

    $deleteResponse = $this->withToken($token)->deleteJson("/api/admin/categories/{$categoryId}");
    $deleteResponse->assertOk();

    $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
});

test('admin cannot delete a category with associated courses', function () {
    $token = categoryAdminToken();

    $category = categories::factory()->create();
    courses::factory()->create(['category_id' => $category->id]);

    $response = $this->withToken($token)->deleteJson("/api/admin/categories/{$category->id}");

    $response->assertStatus(400)
        ->assertJson([
            'status' => false,
            'message' => __('api.category_has_courses'),
        ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
    ]);
});

test('admin receives 404 when deleting non-existent category', function () {
    $token = categoryAdminToken();

    $response = $this->withToken($token)->deleteJson('/api/admin/categories/99999');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => __('api.category_not_found'),
        ]);
});

// -------------------------------------------------------------------------
// 6. Security, Authentication & Role Isolation
// -------------------------------------------------------------------------
test('unauthenticated guest cannot access category endpoints and receives 401 with unified error', function () {
    $category = categories::factory()->create();

    $this->getJson('/api/admin/categories')
        ->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthenticated'),
            'errors' => null,
        ]);

    $this->postJson('/api/admin/categories', ['name' => 'Test'])
        ->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthenticated'),
            'errors' => null,
        ]);

    $this->getJson("/api/admin/categories/{$category->id}")
        ->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthenticated'),
            'errors' => null,
        ]);

    $this->putJson("/api/admin/categories/{$category->id}", ['name' => 'Test'])
        ->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthenticated'),
            'errors' => null,
        ]);

    $this->deleteJson("/api/admin/categories/{$category->id}")
        ->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthenticated'),
            'errors' => null,
        ]);
});

test('instructor cannot access admin category endpoints and receives 403 with unified error', function () {
    $token = categoryInstructorToken();
    $category = categories::factory()->create();

    $this->withToken($token)->getJson('/api/admin/categories')
        ->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthorized'),
            'errors' => null,
        ]);

    $this->withToken($token)->postJson('/api/admin/categories', ['name' => 'Forbidden'])
        ->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthorized'),
            'errors' => null,
        ]);

    $this->withToken($token)->putJson("/api/admin/categories/{$category->id}", ['name' => 'Forbidden'])
        ->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthorized'),
            'errors' => null,
        ]);

    $this->withToken($token)->deleteJson("/api/admin/categories/{$category->id}")
        ->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthorized'),
            'errors' => null,
        ]);
});

test('student cannot access admin category endpoints and receives 403 with unified error', function () {
    $token = categoryStudentToken();
    $category = categories::factory()->create();

    $this->withToken($token)->getJson('/api/admin/categories')
        ->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthorized'),
            'errors' => null,
        ]);

    $this->withToken($token)->postJson('/api/admin/categories', ['name' => 'Forbidden'])
        ->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthorized'),
            'errors' => null,
        ]);

    $this->withToken($token)->putJson("/api/admin/categories/{$category->id}", ['name' => 'Forbidden'])
        ->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthorized'),
            'errors' => null,
        ]);

    $this->withToken($token)->deleteJson("/api/admin/categories/{$category->id}")
        ->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => __('api.unauthorized'),
            'errors' => null,
        ]);
});

test('api v1 version alias works for admin categories', function () {
    $token = categoryAdminToken();
    categories::factory()->create();

    $response = $this->withToken($token)->getJson('/api/v1/admin/categories');

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'message' => __('api.categories_retrieved'),
        ]);
});
