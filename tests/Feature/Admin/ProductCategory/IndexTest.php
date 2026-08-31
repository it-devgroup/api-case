<?php

namespace Tests\Feature\Admin\ProductCategory;

use App\Models\Admin;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_product_categories(): void
    {
        ProductCategory::factory()->count(3)->create();
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/product-categories');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertJsonPath('data.0.type', 'product-categories');
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/product-categories');

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }

    public function test_user_token_cannot_list_product_categories(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/product-categories');

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
    }
}
