<?php

namespace App\Services;

use App\Http\Resources\AdminResource;
use App\Mail\SendOtpMail;
use App\Models\admins;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminAuthService
{
    public function __construct(
        protected Otp $otp = new Otp,
    ) {}

    /**
     * Authenticate an admin and issue a Sanctum token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{status: bool, message: string, data?: array<string, mixed>, code: int}
     */
    public function login(array $credentials): array
    {
        $admin = admins::where('email', $credentials['email'])->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            return [
                'status' => false,
                'message' => __('api.invalid_credentials'),
                'code' => 401,
            ];
        }

        $minutes = (int) config('sanctum.expiration', 10080);
        $expiresAt = $minutes > 0 ? now()->addMinutes($minutes) : null;

        $token = $admin->createToken('admin_auth_token', ['*'], $expiresAt)->plainTextToken;

        return [
            'status' => true,
            'message' => __('api.login_success'),
            'data' => [
                'admin' => new AdminResource($admin),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'code' => 200,
        ];
    }

    /**
     * Invalidate current token for admin.
     *
     * @return array{status: bool, message: string, code: int}
     */
    public function logout(?admins $admin): array
    {
        if (! $admin) {
            return [
                'status' => false,
                'message' => __('api.user_not_found'),
                'code' => 404,
            ];
        }

        $admin->currentAccessToken()?->delete();

        return [
            'status' => true,
            'message' => __('api.logout_success'),
            'code' => 200,
        ];
    }

    /**
     * Retrieve admin profile.
     *
     * @return array{status: bool, message: string, data?: AdminResource, code: int}
     */
    public function profile(?admins $admin): array
    {
        if (! $admin) {
            return [
                'status' => false,
                'message' => __('api.user_not_found'),
                'code' => 404,
            ];
        }

        return [
            'status' => true,
            'message' => __('api.profile_retrieved'),
            'data' => new AdminResource($admin),
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
        $admin = admins::where('email', $email)->first();

        if (! $admin) {
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
     * Verify OTP and reset admin password.
     *
     * @param  array{email: string, otp: string, password: string}  $data
     * @return array{status: bool, message: string, code: int}
     */
    public function resetPassword(array $data): array
    {
        $admin = admins::where('email', $data['email'])->first();

        if (! $admin) {
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

        $admin->password = Hash::make($data['password']);
        $admin->save();

        // Invalidate active tokens
        $admin->tokens()->delete();

        return [
            'status' => true,
            'message' => __('api.password_reset_success'),
            'code' => 200,
        ];
    }
}
