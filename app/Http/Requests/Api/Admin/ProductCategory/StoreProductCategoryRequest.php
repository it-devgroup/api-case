<?php

namespace App\Http\Requests\Api\Admin\ProductCategory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductCategoryRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', 'unique:product_categories,slug'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'array'],
            'meta_title.*' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'array'],
            'meta_description.*' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'og_image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
