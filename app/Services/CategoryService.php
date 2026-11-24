<?php

namespace App\Services;

use App\Http\Resources\CategoryResource;
use App\Models\categories;
use App\utils\ImageManger;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function __construct(
        protected ImageManger $imageManger = new ImageManger,
    ) {}


    /**
     * Retrieve all categories with optional search, pagination, and caching.
     *
     * @param  array<string, mixed>  $filters
     * @return array{status: bool, message: string, data: mixed, code: int}
     */
    public function getAll(array $filters = []): array
    {
        $perPage = isset($filters['per_page']) && is_numeric($filters['per_page']) ? (int) $filters['per_page'] : null;
        $search = $filters['search'] ?? null;

        if ($perPage || $search) {
            $query = categories::query()->withCount('courses');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            $categories = $perPage ? $query->paginate($perPage) : $query->get();
        } else {
            $categories = Cache::remember('categories.all', now()->addHours(24), function () {
                return categories::withCount('courses')->get();
            });
        }

        return [
            'status' => true,
            'message' => __('api.categories_retrieved'),
            'data' => CategoryResource::collection($categories),
            'code' => 200,
        ];
    }

    /**
     * Retrieve a single category by ID or unique slug with caching.
     *
     * @return array{status: bool, message: string, data?: CategoryResource, code: int}
     */
    public function getById(int|string $id): array
    {
        $cacheKey = "categories.item.{$id}";

        $category = Cache::remember($cacheKey, now()->addHours(24), function () use ($id) {
            return is_numeric($id)
                ? categories::withCount('courses')->where('id', $id)->first()
                : categories::withCount('courses')->where('slug', $id)->first();
        });

        if (! $category) {
            Cache::forget($cacheKey);

            return [
                'status' => false,
                'message' => __('api.category_not_found'),
                'code' => 404,
            ];
        }

        return [
            'status' => true,
            'message' => __('api.category_retrieved'),
            'data' => new CategoryResource($category),
            'code' => 200,
        ];
    }

    /**
     * Create a new category and invalidate relevant caches.
     *
     * @param  array<string, mixed>  $data
     * @return array{status: bool, message: string, data: CategoryResource, code: int}
     */
    public function create(array $data): array
    {
        $icon = $data['icon'];
        if ($icon instanceof UploadedFile) {
            $icon = $this->imageManger->uploadSingeImage('/', $icon, 'categories');
        }

        $categoryData = [
            'name' => $data['name'],
            'small_description' => $data['small_description'],
            'icon' => $icon,
        ];

        if (! empty($data['slug'])) {
            $categoryData['slug'] = SlugService::createSlug(categories::class, 'slug', $data['slug']);
        }

        $category = categories::create($categoryData);

        $this->invalidateCache($category);

        return [
            'status' => true,
            'message' => __('api.category_created'),
            'data' => new CategoryResource($category),
            'code' => 201,
        ];
    }

    /**
     * Update an existing category and invalidate relevant caches.
     *
     * @param  array<string, mixed>  $data
     * @return array{status: bool, message: string, data?: CategoryResource, code: int}
     */
    public function update(int|string $id, array $data): array
    {
        $category = is_numeric($id)
            ? categories::where('id', $id)->first()
            : categories::where('slug', $id)->first();

        if (! $category) {
            return [
                'status' => false,
                'message' => __('api.category_not_found'),
                'code' => 404,
            ];
        }

        $oldSlug = $category->slug;

        $updateData = [];
        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('slug', $data) && ! empty($data['slug'])) {
            $updateData['slug'] = SlugService::createSlug($category, 'slug', $data['slug']);
        }
        if (array_key_exists('small_description', $data)) {
            $updateData['small_description'] = $data['small_description'];
        }
        if (array_key_exists('icon', $data)) {
            $icon = $data['icon'];
            if ($icon instanceof UploadedFile) {
                if ($category->icon) {
                    $oldPath = str_starts_with($category->icon, 'uploads/categories/')
                        ? $category->icon
                        : 'uploads/categories/'.$category->icon;
                    $this->imageManger->deleteImageFromLocal($oldPath);
                }
                $updateData['icon'] = $this->imageManger->uploadSingeImage('/', $icon, 'categories');
            } else {
                $updateData['icon'] = $icon;
            }
        }

        $category->update($updateData);

        $this->invalidateCache($category, $oldSlug);

        return [
            'status' => true,
            'message' => __('api.category_updated'),
            'data' => new CategoryResource($category->fresh()),
            'code' => 200,
        ];
    }

    /**
     * Delete a category if it has no associated courses and invalidate relevant caches.
     *
     * @return array{status: bool, message: string, data?: null, code: int}
     */
    public function delete(int|string $id): array
    {
        $category = is_numeric($id)
            ? categories::where('id', $id)->first()
            : categories::where('slug', $id)->first();

        if (! $category) {
            return [
                'status' => false,
                'message' => __('api.category_not_found'),
                'code' => 404,
            ];
        }

        // Business rule: Do not delete if category has courses
        if ($category->courses()->exists()) {
            return [
                'status' => false,
                'message' => __('api.category_has_courses'),
                'code' => 400,
            ];
        }

        $categoryId = $category->id;
        $categorySlug = $category->slug;
        $categoryIcon = $category->icon;

        $category->delete();

        if ($categoryIcon) {
            $this->imageManger->deleteImageFromLocal('uploads/categories/'.$categoryIcon);
        }

        $this->forgetCategoryCaches($categoryId, $categorySlug);

        return [
            'status' => true,
            'message' => __('api.category_deleted'),
            'code' => 200,
            'data' => null,
        ];
    }

    /**
     * Invalidate caches associated with category.
     */
    protected function invalidateCache(categories $category, ?string $oldSlug = null): void
    {
        $this->forgetCategoryCaches($category->id, $category->slug);

        if ($oldSlug && $oldSlug !== $category->slug) {
            Cache::forget("categories.item.{$oldSlug}");
        }
    }

    /**
     * Clear all cached keys for categories.
     */
    protected function forgetCategoryCaches(int|string $id, ?string $slug = null): void
    {
        Cache::forget('categories.all');
        Cache::forget("categories.item.{$id}");

        if ($slug) {
            Cache::forget("categories.item.{$slug}");
        }
    }
}
