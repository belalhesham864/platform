<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\InstructorForgotPasswordRequest;
use App\Http\Requests\Instructor\InstructorLoginRequest;
use App\Http\Requests\Instructor\InstructorResetPasswordRequest;
use App\Services\InstructorAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InstructorAuthService $instructorAuthService,
    ) {}

    /**
     * Instructor login.
     */
    public function login(InstructorLoginRequest $request): JsonResponse
    {
        $result = $this->instructorAuthService->login($request->validated());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Get authenticated instructor profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $result = $this->instructorAuthService->profile($request->user());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Instructor logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $result = $this->instructorAuthService->logout($request->user());

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Send OTP for instructor password reset.
     */
    public function forgotPassword(InstructorForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->instructorAuthService->sendOtp($request->validated('email'));

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Verify OTP and reset instructor password.
     */
    public function resetPassword(InstructorResetPasswordRequest $request): JsonResponse
    {
        $result = $this->instructorAuthService->resetPassword($request->validated());

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }
}
