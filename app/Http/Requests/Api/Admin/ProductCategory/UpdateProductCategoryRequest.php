<?php

namespace App\Http\Requests\Api\Admin\ProductCategory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('product_categories', 'slug')->ignore($this->route('product_category')),
            ],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_title' => ['sometimes', 'nullable', 'array'],
            'meta_title.*' => ['nullable', 'string'],
            'meta_description' => ['sometimes', 'nullable', 'array'],
            'meta_description.*' => ['nullable', 'string'],
            'meta_keywords' => ['sometimes', 'nullable', 'string'],
            'og_image' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
