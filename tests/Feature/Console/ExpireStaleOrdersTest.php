<?php

namespace Tests\Feature\Console;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireStaleOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_expires_stale_orders_and_restocks_items(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3]);
        $staleOrder = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'expires_at' => now()->subMinute(),
        ]);
        OrderItem::factory()->create(['order_id' => $staleOrder->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->artisan('orders:expire-stale')->assertExitCode(0);

        $this->assertDatabaseHas('orders', ['id' => $staleOrder->id, 'status' => OrderStatus::Expired->value]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 5]);
    }

    public function test_it_does_not_touch_orders_still_within_their_hold_window(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3]);
        $freshOrder = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'expires_at' => now()->addMinutes(10),
        ]);
        OrderItem::factory()->create(['order_id' => $freshOrder->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->artisan('orders:expire-stale')->assertExitCode(0);

        $this->assertDatabaseHas('orders', ['id' => $freshOrder->id, 'status' => OrderStatus::Pending->value]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 3]);
    }

    public function test_it_does_not_touch_already_paid_orders(): void
    {
        $paidOrder = Order::factory()->paid()->create(['expires_at' => now()->subMinute()]);

        $this->artisan('orders:expire-stale')->assertExitCode(0);

        $this->assertDatabaseHas('orders', ['id' => $paidOrder->id, 'status' => OrderStatus::Paid->value]);
    }
}
