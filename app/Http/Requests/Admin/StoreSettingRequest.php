<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
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
            'group' => ['nullable', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'],
            'value' => ['nullable'],
            'type' => ['nullable', 'string', 'max:50'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required' => __('validation.custom.key.required'),
            'key.unique' => __('validation.custom.key.unique'),
        ];
    }
}
