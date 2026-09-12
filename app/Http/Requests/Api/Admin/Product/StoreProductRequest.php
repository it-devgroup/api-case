<?php

namespace App\Http\Requests\Api\Admin\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'sku' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'title' => ['required', 'array', 'min:1'],
            'title.*' => ['required', 'string'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
            'categoryId' => ['nullable', 'integer', 'exists:product_categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'stockQuantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'metaTitle' => ['nullable', 'array'],
            'metaTitle.*' => ['nullable', 'string'],
            'metaDescription' => ['nullable', 'array'],
            'metaDescription.*' => ['nullable', 'string'],
            'metaKeywords' => ['nullable', 'string'],
            'ogImage' => ['nullable', 'string', 'max:255'],
        ];
    }
}
