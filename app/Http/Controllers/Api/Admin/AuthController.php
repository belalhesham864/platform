<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminForgotPasswordRequest;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Requests\Admin\AdminResetPasswordRequest;
use App\Services\AdminAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AdminAuthService $adminAuthService,
    ) {}

    /**
     * Admin login.
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->adminAuthService->login($request->validated());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Get authenticated admin profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $result = $this->adminAuthService->profile($request->user());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Admin logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $result = $this->adminAuthService->logout($request->user());

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Send OTP for admin password reset.
     */
    public function forgotPassword(AdminForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->adminAuthService->sendOtp($request->validated('email'));

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Verify OTP and reset admin password.
     */
    public function resetPassword(AdminResetPasswordRequest $request): JsonResponse
    {
        $result = $this->adminAuthService->resetPassword($request->validated());

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }
}
