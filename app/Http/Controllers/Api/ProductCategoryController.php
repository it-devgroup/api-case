<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $productCategories = ProductCategory::query()->paginate();

        return ProductCategoryResource::collection($productCategories)->response();
    }
}
