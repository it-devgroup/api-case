<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Auth\LoginAdminRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(LoginAdminRequest $request): JsonResponse
    {
        $admin = Admin::where('email', $request->string('email'))
            ->where('is_active', true)
            ->first();

        if (! $admin || ! Hash::check($request->string('password'), $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $admin->createToken('api-token', ['admin'])->plainTextToken;

        return (new AdminResource($admin))
            ->additional(['meta' => [
                'message' => 'Logged in successfully.',
                'token' => $token,
            ]])
            ->response();
    }
}
