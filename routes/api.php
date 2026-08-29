<?php

use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
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
