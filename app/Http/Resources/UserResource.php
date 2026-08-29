<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * @var array<int, string>
     */
    public $attributes = ['name', 'email', 'email_verified_at', 'created_at', 'updated_at'];

    public function toType(Request $request): string
    {
        return 'users';
    }
}
