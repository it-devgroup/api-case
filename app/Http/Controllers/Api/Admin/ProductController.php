<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Product\StoreProductRequest;
use App\Http\Requests\Api\Admin\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\JsonApi;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Maps camelCase request fields to the model's snake_case attributes.
     */
    private const ATTRIBUTE_MAP = [
        'isActive' => 'is_active',
        'categoryId' => 'category_id',
        'stockQuantity' => 'stock_quantity',
        'metaTitle' => 'meta_title',
        'metaDescription' => 'meta_description',
        'metaKeywords' => 'meta_keywords',
        'ogImage' => 'og_image',
    ];

    public function index(): JsonResponse
    {
        $products = Product::query()->paginate();

        return ProductResource::collection($products)->response();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($this->mapAttributes($request->validated()))->fresh();

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): JsonResponse
    {
        return (new ProductResource($product))->response();
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($this->mapAttributes($request->validated()));

        return (new ProductResource($product))->response();
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return JsonApi::meta(['message' => 'Product deleted successfully.']);
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
