<?php

use App\Http\Controllers\Api\Admin\Auth\LoginController;
use App\Http\Controllers\Api\Admin\Auth\MeController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ProductCategoryController;
use App\Http\Controllers\Api\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('admin/login', [LoginController::class, 'store'])->middleware('throttle:login');

Route::get('admin/me', [MeController::class, 'show'])->middleware(['auth:sanctum', 'auth.admin']);

Route::middleware(['auth:sanctum', 'auth.admin'])->group(function () {
    Route::get('admin/product-categories', [ProductCategoryController::class, 'index']);
    Route::post('admin/product-categories', [ProductCategoryController::class, 'store']);
    Route::get('admin/product-categories/export', [ProductCategoryController::class, 'export']);
    Route::post('admin/product-categories/import', [ProductCategoryController::class, 'import']);
    Route::get('admin/product-categories/{product_category}', [ProductCategoryController::class, 'show']);
    Route::patch('admin/product-categories/{product_category}', [ProductCategoryController::class, 'update']);
    Route::delete('admin/product-categories/{product_category}', [ProductCategoryController::class, 'destroy']);

    Route::get('admin/products', [ProductController::class, 'index']);
    Route::post('admin/products', [ProductController::class, 'store']);
    Route::get('admin/products/export', [ProductController::class, 'export']);
    Route::post('admin/products/import', [ProductController::class, 'import']);
    Route::get('admin/products/{product}', [ProductController::class, 'show']);
    Route::patch('admin/products/{product}', [ProductController::class, 'update']);
    Route::delete('admin/products/{product}', [ProductController::class, 'destroy']);

    Route::get('admin/orders', [OrderController::class, 'index']);
    Route::get('admin/orders/export', [OrderController::class, 'export']);
    Route::get('admin/orders/{order}', [OrderController::class, 'show']);
    Route::patch('admin/orders/{order}', [OrderController::class, 'update']);
});
