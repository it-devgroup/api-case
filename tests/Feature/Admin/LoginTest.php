<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_login_and_receive_token(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertJsonPath('data.type', 'admins');
        $response->assertJsonPath('data.attributes.email', 'admin@example.com');
        $this->assertIsString($response->json('meta.token'));
    }

    public function test_inactive_admin_cannot_login(): void
    {
        Admin::factory()->inactive()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/email');
    }

    public function test_soft_deleted_admin_cannot_login(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $admin->delete();

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/email');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/email');
    }

    public function test_admin_registration_route_does_not_exist(): void
    {
        $response = $this->postJson('/api/admin/register', [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertJsonApiError($response, '404');
    }
}
