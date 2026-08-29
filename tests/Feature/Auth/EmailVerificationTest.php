<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyUserEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function signedVerificationUrl(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => $hash ?? sha1($user->getEmailForVerification()),
            ],
        );
    }

    public function test_valid_signed_url_verifies_email(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->getJson($this->signedVerificationUrl($user));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertJsonPath('meta.message', 'Email address verified successfully.');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_mismatched_hash_is_rejected_even_with_valid_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->getJson($this->signedVerificationUrl($user, sha1('someone-else@example.com')));

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unsigned_url_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->getJson("/api/email/verify/{$user->id}/".sha1($user->getEmailForVerification()));

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verifying_an_already_verified_email_is_idempotent(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson($this->signedVerificationUrl($user));

        $response->assertOk();
        $response->assertJsonPath('meta.message', 'Email address already verified.');
    }

    public function test_authenticated_unverified_user_can_resend_verification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/email/verification-notification');

        $response->assertOk();
        $response->assertJsonPath('meta.message', 'Verification link sent.');
        Notification::assertSentTo($user, VerifyUserEmail::class);
    }

    public function test_resend_is_noop_when_already_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/email/verification-notification');

        $response->assertOk();
        $response->assertJsonPath('meta.message', 'Email address already verified.');
        Notification::assertNotSentTo($user, VerifyUserEmail::class);
    }

    public function test_resend_requires_authentication(): void
    {
        $response = $this->postJson('/api/email/verification-notification');

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
