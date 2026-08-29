<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_token_cannot_access_admin_only_route(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/me');

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
    }

    public function test_admin_token_cannot_access_user_only_route(): void
    {
        $admin = Admin::factory()->create();
        $token = $admin->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
    }

    public function test_admin_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/me');

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }

    public function test_user_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
