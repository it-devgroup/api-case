<?php

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_product(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'sku' => 'SKU-0001',
            'slug' => 'phone-x',
            'title' => ['en' => 'Phone X'],
            'description' => ['en' => 'A great phone.'],
            'is_active' => true,
            'category_id' => $category->id,
            'price' => 599.99,
            'stock_quantity' => 25,
            'image' => 'https://example.com/phone-x.jpg',
            'meta_title' => ['en' => 'Phone X | Example'],
            'meta_description' => ['en' => 'Buy Phone X.'],
            'meta_keywords' => 'phone, smartphone',
            'og_image' => 'https://example.com/phone-x-og.jpg',
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/products/{$product->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $product->id);
        $response->assertJsonPath('data.type', 'products');
        $response->assertJsonPath('data.attributes.sku', 'SKU-0001');
        $response->assertJsonPath('data.attributes.slug', 'phone-x');
        $response->assertJsonPath('data.attributes.title.en', 'Phone X');
        $response->assertJsonPath('data.attributes.description.en', 'A great phone.');
        $response->assertJsonPath('data.attributes.is_active', true);
        $response->assertJsonPath('data.attributes.category_id', $category->id);
        $response->assertJsonPath('data.attributes.price', 599.99);
        $response->assertJsonPath('data.attributes.stock_quantity', 25);
        $response->assertJsonPath('data.attributes.image', 'https://example.com/phone-x.jpg');
        $response->assertJsonPath('data.attributes.meta_title.en', 'Phone X | Example');
        $response->assertJsonPath('data.attributes.meta_description.en', 'Buy Phone X.');
        $response->assertJsonPath('data.attributes.meta_keywords', 'phone, smartphone');
        $response->assertJsonPath('data.attributes.og_image', 'https://example.com/phone-x-og.jpg');
    }

    public function test_returns_404_for_missing_product(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/products/999999');

        $response->assertNotFound();
        $this->assertJsonApiError($response, '404');
    }

    public function test_show_requires_authentication(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/admin/products/{$product->id}");

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
