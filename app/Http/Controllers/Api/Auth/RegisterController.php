<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function store(RegisterUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $user->sendEmailVerificationNotification();

        return (new UserResource($user))
            ->additional(['meta' => [
                'message' => 'Registered successfully. Please check your email to verify your account.',
            ]])
            ->response()
            ->setStatusCode(201);
    }
}
