<?php

use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('register', [RegisterController::class, 'store']);
Route::post('login', [LoginController::class, 'store'])->middleware('throttle:login');
Route::post('logout', [LogoutController::class, 'store'])->middleware('auth:sanctum');

Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware('signed')
    ->name('api.verification.verify');

Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'auth.user', 'throttle:resend-verification']);

Route::get('me', [MeController::class, 'show'])->middleware(['auth:sanctum', 'auth.user']);

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);
Route::get('product-categories', [ProductCategoryController::class, 'index']);

Route::post('webhooks/stripe', StripeWebhookController::class);

Route::middleware(['auth:sanctum', 'auth.user'])->group(function () {
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/checkout', [OrderController::class, 'checkout']);
});
