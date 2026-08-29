<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\JsonApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(LoginUserRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return JsonApi::error(
                '403',
                'Forbidden',
                'Your email address is not verified.',
                code: 'email_not_verified',
            );
        }

        $token = $user->createToken('api-token', ['user'])->plainTextToken;

        return (new UserResource($user))
            ->additional(['meta' => [
                'message' => 'Logged in successfully.',
                'token' => $token,
            ]])
            ->response();
    }
}
