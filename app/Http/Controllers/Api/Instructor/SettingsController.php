<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SettingsService $settingsService,
    ) {}

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
}
