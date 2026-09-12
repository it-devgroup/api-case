<?php

namespace Tests\Feature\Order;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_checkout_for_a_pending_order(): void
    {
        $this->app->instance(PaymentGateway::class, $gateway = new FakePaymentGateway);

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/orders/{$order->id}/checkout");

        $response->assertOk();
        $response->assertJsonPath('data.checkoutUrl', 'https://checkout.stripe.com/pay/cs_test_fake123');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'stripe_checkout_session_id' => 'cs_test_fake123',
        ]);
        $this->assertCount(1, $gateway->orders);
    }

    public function test_checkout_is_rejected_for_a_non_pending_order(): void
    {
        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);

        $user = User::factory()->create();
        $order = Order::factory()->paid()->create(['user_id' => $user->id]);

        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/orders/{$order->id}/checkout");

        $this->assertJsonApiError($response, '409');
    }

    public function test_checkout_is_rejected_for_an_expired_order(): void
    {
        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'expires_at' => now()->subMinute(),
        ]);

        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/orders/{$order->id}/checkout");

        $this->assertJsonApiError($response, '409');
    }

    public function test_non_owner_cannot_checkout_the_order(): void
    {
        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);

        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/orders/{$order->id}/checkout");

        $response->assertForbidden();
    }
}
