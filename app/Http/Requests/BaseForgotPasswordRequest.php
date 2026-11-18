<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseForgotPasswordRequest extends FormRequest
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
        ];
    }
}
