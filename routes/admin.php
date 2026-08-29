<?php

use App\Http\Controllers\Api\Admin\Auth\LoginController;
use App\Http\Controllers\Api\Admin\Auth\MeController;
use Illuminate\Support\Facades\Route;

Route::post('admin/login', [LoginController::class, 'store'])->middleware('throttle:login');

Route::get('admin/me', [MeController::class, 'show'])->middleware(['auth:sanctum', 'auth.admin']);
