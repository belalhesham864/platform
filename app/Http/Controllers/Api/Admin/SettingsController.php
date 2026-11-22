<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSettingRequest;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Services\SettingsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SettingsService $settingsService,
    ) {}

    /**
     * List all settings.
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->settingsService->getAll($request->all());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Retrieve grouped public settings.
     */
    public function publicSettings(): JsonResponse
    {
        $result = $this->settingsService->getPublicSettings();

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Show a specific setting by ID or key.
     */
    public function show(int|string $setting): JsonResponse
    {
        $result = $this->settingsService->getById($setting);

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Create a new setting.
     */
    public function store(StoreSettingRequest $request): JsonResponse
    {
        $result = $this->settingsService->create($request->validated());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Update an existing setting.
     */
    public function update(UpdateSettingRequest $request, int|string $setting): JsonResponse
    {
        $result = $this->settingsService->update($setting, $request->validated());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }

    /**
     * Delete a setting.
     */
    public function destroy(int|string $setting): JsonResponse
    {
        $result = $this->settingsService->delete($setting);

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }
}
