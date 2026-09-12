<?php

namespace Tests\Feature\Admin\Order;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_an_order_including_its_payments(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);
        Payment::factory()->succeeded()->create(['order_id' => $order->id]);

        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/orders/{$order->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $order->id);
        $this->assertCount(1, $response->json('data.attributes.payments'));
    }

    public function test_show_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("/api/admin/orders/{$order->id}");

        $response->assertUnauthorized();
    }
}
