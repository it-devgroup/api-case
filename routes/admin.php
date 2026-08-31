<?php

use App\Http\Controllers\Api\Admin\Auth\LoginController;
use App\Http\Controllers\Api\Admin\Auth\MeController;
use App\Http\Controllers\Api\Admin\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::post('admin/login', [LoginController::class, 'store'])->middleware('throttle:login');

Route::get('admin/me', [MeController::class, 'show'])->middleware(['auth:sanctum', 'auth.admin']);

Route::middleware(['auth:sanctum', 'auth.admin'])->group(function () {
    Route::get('admin/product-categories', [ProductCategoryController::class, 'index']);
    Route::post('admin/product-categories', [ProductCategoryController::class, 'store']);
    Route::get('admin/product-categories/{product_category}', [ProductCategoryController::class, 'show']);
    Route::patch('admin/product-categories/{product_category}', [ProductCategoryController::class, 'update']);
    Route::delete('admin/product-categories/{product_category}', [ProductCategoryController::class, 'destroy']);
});
