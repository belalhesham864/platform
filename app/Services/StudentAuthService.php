<?php

namespace App\Services;

use App\Http\Resources\StudentResource;
use App\Mail\SendOtpMail;
use App\Models\User;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StudentAuthService
{
    public function __construct(
        protected Otp $otp = new Otp,
    ) {}

    /**
     * Register a new student in the users table.
     *
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{status: bool, message: string, data: StudentResource, code: int}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return [
            'status' => true,
            'message' => __('api.registered_success'),
            'data' => new StudentResource($user),
            'code' => 201,
        ];
    }

    /**
     * Authenticate a student and issue a Sanctum token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{status: bool, message: string, data?: array<string, mixed>, code: int}
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return [
                'status' => false,
                'message' => __('api.invalid_credentials'),
                'code' => 401,
            ];
        }

        $minutes = (int) config('sanctum.expiration', 10080);
        $expiresAt = $minutes > 0 ? now()->addMinutes($minutes) : null;

        $token = $user->createToken('student_auth_token', ['*'], $expiresAt)->plainTextToken;

        return [
            'status' => true,
            'message' => __('api.login_success'),
            'data' => [
                'student' => new StudentResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'code' => 200,
        ];
    }

    /**
     * Invalidate current token for student.
     *
     * @return array{status: bool, message: string, code: int}
     */
    public function logout(?User $user): array
    {
        if (! $user) {
            return [
                'status' => false,
                'message' => __('api.user_not_found'),
                'code' => 404,
            ];
        }

        $user->currentAccessToken()?->delete();

        return [
            'status' => true,
            'message' => __('api.logout_success'),
            'code' => 200,
        ];
    }

    /**
     * Retrieve student profile.
     *
     * @return array{status: bool, message: string, data?: StudentResource, code: int}
     */
    public function profile(?User $user): array
    {
        if (! $user) {
            return [
                'status' => false,
                'message' => __('api.user_not_found'),
                'code' => 404,
            ];
        }

        return [
            'status' => true,
            'message' => __('api.profile_retrieved'),
            'data' => new StudentResource($user),
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
        $user = User::where('email', $email)->first();

        if (! $user) {
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
     * Verify OTP and reset student password.
     *
     * @param  array{email: string, otp: string, password: string}  $data
     * @return array{status: bool, message: string, code: int}
     */
    public function resetPassword(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
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

        $user->password = Hash::make($data['password']);
        $user->save();

        // Invalidate active tokens
        $user->tokens()->delete();

        return [
            'status' => true,
            'message' => __('api.password_reset_success'),
            'code' => 200,
        ];
    }
}
