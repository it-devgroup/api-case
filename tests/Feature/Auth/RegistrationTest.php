<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyUserEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_verification_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertJsonPath('data.type', 'users');
        $response->assertJsonPath('data.attributes.email', 'jane@example.com');
        $response->assertJsonPath(
            'meta.message',
            'Registered successfully. Please check your email to verify your account.',
        );

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertSame((string) $user->id, $response->json('data.id'));

        Notification::assertSentTo($user, VerifyUserEmail::class);
    }

    public function test_registration_requires_name_email_and_password(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable();
        $this->assertJsonApiErrorPointers($response, [
            '/data/attributes/name',
            '/data/attributes/email',
            '/data/attributes/password',
        ]);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/email');
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/password');
    }
}
