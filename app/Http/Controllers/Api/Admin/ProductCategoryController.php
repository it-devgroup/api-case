<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\Api\Admin\ProductCategory\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Support\JsonApi;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    /**
     * Maps camelCase request fields to the model's snake_case attributes.
     */
    private const ATTRIBUTE_MAP = [
        'metaTitle' => 'meta_title',
        'metaDescription' => 'meta_description',
        'metaKeywords' => 'meta_keywords',
        'ogImage' => 'og_image',
    ];

    public function index(): JsonResponse
    {
        $productCategories = ProductCategory::query()->paginate();

        return ProductCategoryResource::collection($productCategories)->response();
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $productCategory = ProductCategory::create($this->mapAttributes($request->validated()));

        return (new ProductCategoryResource($productCategory))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ProductCategory $product_category): JsonResponse
    {
        return (new ProductCategoryResource($product_category))->response();
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $product_category): JsonResponse
    {
        $product_category->update($this->mapAttributes($request->validated()));

        return (new ProductCategoryResource($product_category))->response();
    }

    public function destroy(ProductCategory $product_category): JsonResponse
    {
        $product_category->delete();

        return JsonApi::meta(['message' => 'Product category deleted successfully.']);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapAttributes(array $validated): array
    {
        $attributes = [];

        foreach ($validated as $key => $value) {
            $attributes[self::ATTRIBUTE_MAP[$key] ?? $key] = $value;
        }

        return $attributes;
    }
}
