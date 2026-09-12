<?php

namespace Tests\Feature\Admin\Order;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_a_paid_order_as_refunded(): void
    {
        $order = Order::factory()->paid()->create();
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/orders/{$order->id}", ['status' => 'refunded']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.status', 'refunded');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Refunded->value]);
    }

    public function test_status_must_be_a_valid_enum_value(): void
    {
        $order = Order::factory()->create();
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/orders/{$order->id}", ['status' => 'not-a-status']);

        $response->assertUnprocessable();
        $this->assertJsonApiErrorPointers($response, ['/data/attributes/status']);
    }

    public function test_update_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'cancelled']);

        $response->assertUnauthorized();
    }
}
