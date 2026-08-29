<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Support\JsonApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return JsonApi::meta(['message' => 'Email address already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return JsonApi::meta(['message' => 'Verification link sent.']);
    }
}
