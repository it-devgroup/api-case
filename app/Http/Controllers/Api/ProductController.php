<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()->where('is_active', true)->paginate();

        return ProductResource::collection($products)->response();
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        return (new ProductResource($product))->response();
    }
}
