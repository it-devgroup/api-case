<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_order_and_stock_is_reserved(): void
    {
        $product = Product::factory()->create(['price' => 10.00, 'stock_quantity' => 5]);
        $token = User::factory()->create()->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/orders', [
                'items' => [
                    ['productId' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'orders');
        $response->assertJsonPath('data.attributes.status', 'pending');
        $response->assertJsonPath('data.attributes.subtotal', 2000);
        $response->assertJsonPath('data.attributes.total', 2000);
        $response->assertJsonPath('data.attributes.currency', 'nok');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 3]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'unit_price' => 1000,
            'quantity' => 2,
            'line_total' => 2000,
        ]);
    }

    public function test_order_creation_fails_with_insufficient_stock(): void
    {
        $product = Product::factory()->create(['price' => 10.00, 'stock_quantity' => 1]);
        $token = User::factory()->create()->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/orders', [
                'items' => [
                    ['productId' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', null, 'insufficient_stock');
        $response->assertJsonPath('errors.0.detail', fn ($detail) => str_contains($detail, $product->sku));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 1]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_inactive_products_are_rejected(): void
    {
        $product = Product::factory()->inactive()->create(['stock_quantity' => 5]);
        $token = User::factory()->create()->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/orders', [
                'items' => [
                    ['productId' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiErrorPointers($response, ['/data/attributes/items.0.productId']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_only_one_of_two_concurrent_requests_for_the_last_unit_succeeds(): void
    {
        $product = Product::factory()->create(['price' => 10.00, 'stock_quantity' => 1]);
        $token = User::factory()->create()->createToken('test', ['user'])->plainTextToken;

        $payload = ['items' => [['productId' => $product->id, 'quantity' => 1]]];

        $first = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/orders', $payload);
        $second = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/orders', $payload);

        $statuses = collect([$first->status(), $second->status()])->sort()->values()->all();

        $this->assertSame([201, 422], $statuses);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 0]);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_store_requires_authentication(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $response = $this->postJson('/api/orders', [
            'items' => [['productId' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertUnauthorized();
    }
}
