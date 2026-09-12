<?php

namespace App\Http\Requests\Api\Admin\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'sku' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('products', 'slug')->ignore($this->route('product')),
            ],
            'title' => ['sometimes', 'array', 'min:1'],
            'title.*' => ['required', 'string'],
            'description' => ['sometimes', 'nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'isActive' => ['sometimes', 'boolean'],
            'categoryId' => ['sometimes', 'nullable', 'integer', 'exists:product_categories,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stockQuantity' => ['sometimes', 'integer', 'min:0'],
            'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metaTitle' => ['sometimes', 'nullable', 'array'],
            'metaTitle.*' => ['nullable', 'string'],
            'metaDescription' => ['sometimes', 'nullable', 'array'],
            'metaDescription.*' => ['nullable', 'string'],
            'metaKeywords' => ['sometimes', 'nullable', 'string'],
            'ogImage' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
