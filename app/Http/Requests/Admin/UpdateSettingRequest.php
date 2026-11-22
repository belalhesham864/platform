<?php

namespace App\Http\Requests\Admin;

use App\Models\settings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
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
        $settingParam = $this->route('setting') ?? $this->route('id');
        $settingId = is_object($settingParam) ? $settingParam->id : $settingParam;

        if ($settingId && ! is_numeric($settingId)) {
            $settingModel = settings::where('key', (string) $settingId)->first();
            $settingId = $settingModel?->id;
        }

        return [
            'group' => ['sometimes', 'nullable', 'string', 'max:255'],
            'key' => ['sometimes', 'required', 'string', 'max:255', 'unique:settings,key,'.$settingId],
            'value' => ['sometimes', 'nullable'],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_public' => ['sometimes', 'nullable', 'boolean'],
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
