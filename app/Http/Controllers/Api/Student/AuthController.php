<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentForgotPasswordRequest;
use App\Http\Requests\Student\StudentLoginRequest;
use App\Http\Requests\Student\StudentRegisterRequest;
use App\Http\Requests\Student\StudentResetPasswordRequest;
use App\Services\StudentAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StudentAuthService $studentAuthService,
    ) {}

    /**
     * Register a new student.
     */
    public function register(StudentRegisterRequest $request): JsonResponse
    {
        $result = $this->studentAuthService->register($request->validated());

        return $this->apiResponse(
            data: $result['data'],
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Student login.
     */
    public function login(StudentLoginRequest $request): JsonResponse
    {
        $result = $this->studentAuthService->login($request->validated());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Student logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $result = $this->studentAuthService->logout($request->user());

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Get authenticated student profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $result = $this->studentAuthService->profile($request->user());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Send OTP for student password reset.
     */
    public function forgotPassword(StudentForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->studentAuthService->sendOtp($request->validated('email'));

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }

    /**
     * Verify OTP and reset student password.
     */
    public function resetPassword(StudentResetPasswordRequest $request): JsonResponse
    {
        $result = $this->studentAuthService->resetPassword($request->validated());

        return $this->apiResponse(
            data: null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: null,
            status: $result['status']
        );
    }
}
