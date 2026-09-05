<?php

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_product(): void
    {
        $product = Product::factory()->create();
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/products/{$product->id}");

        $response->assertOk();
        $response->assertJsonPath('meta.message', 'Product deleted successfully.');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_returns_404_for_missing_product(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/admin/products/999999');

        $response->assertNotFound();
        $this->assertJsonApiError($response, '404');
    }

    public function test_destroy_requires_authentication(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/admin/products/{$product->id}");

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
