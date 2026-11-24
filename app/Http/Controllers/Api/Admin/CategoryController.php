<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Services\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CategoryService $categoryService,
    ) {}

    /**
     * List all categories.
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->categoryService->getAll($request->all());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Show a specific category by ID or slug.
     */
    public function show(int|string $category): JsonResponse
    {
        $result = $this->categoryService->getById($category);

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Create a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $result = $this->categoryService->create($request->validated());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, int|string $category): JsonResponse
    {
        $result = $this->categoryService->update($category, $request->validated());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Delete a category.
     */
    public function destroy(int|string $category): JsonResponse
    {
        $result = $this->categoryService->delete($category);

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }
}
