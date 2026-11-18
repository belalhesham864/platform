<?php

namespace App\Services;

use App\Http\Resources\InstructorResource;
use App\Mail\SendOtpMail;
use App\Models\instructors;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class InstructorAuthService
{
    public function __construct(
        protected Otp $otp = new Otp,
    ) {}

    /**
     * Authenticate an instructor and issue a Sanctum token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{status: bool, message: string, data?: array<string, mixed>, code: int}
     */
    public function login(array $credentials): array
    {
        $instructor = instructors::where('email', $credentials['email'])->first();

        if (! $instructor || ! Hash::check($credentials['password'], $instructor->password)) {
            return [
                'status' => false,
                'message' => __('api.invalid_credentials'),
                'code' => 401,
            ];
        }

        if (! $instructor->is_active) {
            return [
                'status' => false,
                'message' => __('api.account_deactivated'),
                'code' => 403,
            ];
        }

        $minutes = (int) config('sanctum.expiration', 10080);
        $expiresAt = $minutes > 0 ? now()->addMinutes($minutes) : null;

        $token = $instructor->createToken('instructor_auth_token', ['*'], $expiresAt)->plainTextToken;

        return [
            'status' => true,
            'message' => __('api.login_success'),
            'data' => [
                'instructor' => new InstructorResource($instructor),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'code' => 200,
        ];
    }

    /**
     * Invalidate current token for instructor.
     *
     * @return array{status: bool, message: string, code: int}
     */
    public function logout(?instructors $instructor): array
    {
        if (! $instructor) {
            return [
                'status' => false,
                'message' => __('api.user_not_found'),
                'code' => 404,
            ];
        }

        $instructor->currentAccessToken()?->delete();

        return [
            'status' => true,
            'message' => __('api.logout_success'),
            'code' => 200,
        ];
    }

    /**
     * Retrieve instructor profile.
     *
     * @return array{status: bool, message: string, data?: InstructorResource, code: int}
     */
    public function profile(?instructors $instructor): array
    {
        if (! $instructor) {
            return [
                'status' => false,
                'message' => __('api.user_not_found'),
                'code' => 404,
            ];
        }

        return [
            'status' => true,
            'message' => __('api.profile_retrieved'),
            'data' => new InstructorResource($instructor),
            'code' => 200,
        ];
    }

    /**
     * Generate and dispatch OTP for password reset.
     *
     * @return array{status: bool, message: string, code: int}
     */
    public function sendOtp(string $email): array
    {
        $instructor = instructors::where('email', $email)->first();

        if (! $instructor) {
            return [
                'status' => false,
                'message' => __('api.user_not_found'),
                'code' => 404,
            ];
        }

        $otpResult = $this->otp->generate($email, 'numeric', 6, 10);

        Mail::to($email)->send(new SendOtpMail($otpResult->token));

        return [
            'status' => true,
            'message' => __('api.otp_sent'),
            'code' => 200,
        ];
    }

    /**
     * Verify OTP and reset instructor password.
     *
     * @param  array{email: string, otp: string, password: string}  $data
     * @return array{status: bool, message: string, code: int}
     */
    public function resetPassword(array $data): array
    {
        $instructor = instructors::where('email', $data['email'])->first();

        if (! $instructor) {
            return [
                'status' => false,
                'message' => __('api.user_not_found'),
                'code' => 404,
            ];
        }

        $otpValidation = $this->otp->validate($data['email'], $data['otp']);

        if (! $otpValidation->status) {
            return [
                'status' => false,
                'message' => __('api.otp_invalid_or_expired'),
                'code' => 400,
            ];
        }

        $instructor->password = Hash::make($data['password']);
        $instructor->save();

        // Invalidate active tokens
        $instructor->tokens()->delete();

        return [
            'status' => true,
            'message' => __('api.password_reset_success'),
            'code' => 200,
        ];
    }
}
