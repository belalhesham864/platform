<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'small_description' => ['required', 'string'],
            'icon' => $this->hasFile('icon')
                ? ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048']
                : ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'حقل الاسم مطلوب.',
            'name.string' => 'يجب أن يكون الاسم نصاً.',
            'name.max' => 'يجب ألا يتجاوز الاسم 255 حرفاً.',
            'slug.unique' => __('validation.custom.slug.unique'),
            'slug.max' => 'يجب ألا يتجاوز الرابط 255 حرفاً.',
            'small_description.required' => 'حقل الوصف المختصر مطلوب.',
            'icon.required' => 'حقل الأيقونة مطلوب.',
            'icon.image' => 'يجب أن يكون ملف الأيقونة صورة.',
            'icon.mimes' => 'يجب أن تكون الصورة من نوع: jpeg, png, jpg, gif, svg, webp.',
            'icon.max' => 'يجب ألا يتجاوز حجم الصورة 2 ميجابايت.',
        ];
    }
}
