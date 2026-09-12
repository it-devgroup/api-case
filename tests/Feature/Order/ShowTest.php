<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/orders/{$order->id}");

        $response->assertOk();
        $response->assertJsonPath('data.type', 'orders');
        $response->assertJsonPath('data.id', (string) $order->id);
    }

    public function test_non_owner_cannot_view_the_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/orders/{$order->id}");

        $response->assertForbidden();
    }
}
