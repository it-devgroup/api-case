<?php

namespace Tests\Feature\Admin\Order;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_orders(): void
    {
        Order::factory()->count(3)->create();
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/orders');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_filter_orders_by_status(): void
    {
        Order::factory()->paid()->create();
        Order::factory()->create(['status' => OrderStatus::Pending]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/orders?status=paid');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.attributes.status', 'paid');
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/orders');

        $response->assertUnauthorized();
    }

    public function test_user_token_cannot_list_admin_orders(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/orders');

        $response->assertForbidden();
    }
}
