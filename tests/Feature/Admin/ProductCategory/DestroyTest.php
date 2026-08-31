<?php

namespace Tests\Feature\Admin\ProductCategory;

use App\Models\Admin;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_product_category(): void
    {
        $productCategory = ProductCategory::factory()->create();
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/product-categories/{$productCategory->id}");

        $response->assertOk();
        $response->assertJsonPath('meta.message', 'Product category deleted successfully.');
        $this->assertDatabaseMissing('product_categories', ['id' => $productCategory->id]);
    }

    public function test_returns_404_for_missing_product_category(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/admin/product-categories/999999');

        $response->assertNotFound();
        $this->assertJsonApiError($response, '404');
    }

    public function test_destroy_requires_authentication(): void
    {
        $productCategory = ProductCategory::factory()->create();

        $response = $this->deleteJson("/api/admin/product-categories/{$productCategory->id}");

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
