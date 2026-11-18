<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseResetPasswordRequest extends FormRequest
{
    /**
     * Target database table for the account type.
     */
    abstract protected function table(): string;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', "exists:{$this->table()},email"],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('validation.custom.email.required'),
            'email.email' => __('validation.custom.email.email'),
            'email.max' => __('validation.custom.email.max'),
            'email.exists' => __('validation.custom.email.exists'),
            'otp.required' => __('validation.custom.otp.required'),
            'otp.size' => __('validation.custom.otp.size'),
            'password.required' => __('validation.custom.password.required'),
            'password.min' => __('validation.custom.password.min'),
            'password.confirmed' => __('validation.custom.password.confirmed'),
        ];
    }
}
