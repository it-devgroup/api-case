<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Order::factory()->count(2)->create(['user_id' => $user->id]);
        Order::factory()->create(['user_id' => $otherUser->id]);

        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/orders');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertUnauthorized();
    }
}
