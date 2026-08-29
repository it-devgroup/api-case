<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Support\JsonApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return JsonApi::meta(['message' => 'Logged out successfully.']);
    }
}
