<?php

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_product(): void
    {
        $category = ProductCategory::factory()->create();
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/products', [
                'sku' => 'SKU-0001',
                'slug' => 'phone-x',
                'title' => ['en' => 'Phone X', 'no' => 'Telefon X'],
                'description' => ['en' => 'A great phone.'],
                'isActive' => false,
                'categoryId' => $category->id,
                'price' => 599.99,
                'stockQuantity' => 25,
                'image' => 'https://example.com/phone-x.jpg',
                'metaTitle' => ['en' => 'Phone X | Example'],
                'metaDescription' => ['en' => 'Buy Phone X.'],
                'metaKeywords' => 'phone, smartphone',
                'ogImage' => 'https://example.com/phone-x-og.jpg',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'products');
        $response->assertJsonPath('data.attributes.sku', 'SKU-0001');
        $response->assertJsonPath('data.attributes.slug', 'phone-x');
        $response->assertJsonPath('data.attributes.title.en', 'Phone X');
        $response->assertJsonPath('data.attributes.title.no', 'Telefon X');
        $response->assertJsonPath('data.attributes.description.en', 'A great phone.');
        $response->assertJsonPath('data.attributes.isActive', false);
        $response->assertJsonPath('data.attributes.categoryId', $category->id);
        $response->assertJsonPath('data.attributes.price', 599.99);
        $response->assertJsonPath('data.attributes.stockQuantity', 25);
        $response->assertJsonPath('data.attributes.image', 'https://example.com/phone-x.jpg');
        $response->assertJsonPath('data.attributes.metaTitle.en', 'Phone X | Example');
        $response->assertJsonPath('data.attributes.metaDescription.en', 'Buy Phone X.');
        $response->assertJsonPath('data.attributes.metaKeywords', 'phone, smartphone');
        $response->assertJsonPath('data.attributes.ogImage', 'https://example.com/phone-x-og.jpg');

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-0001',
            'slug' => 'phone-x',
            'is_active' => false,
            'category_id' => $category->id,
            'price' => 599.99,
            'stock_quantity' => 25,
            'image' => 'https://example.com/phone-x.jpg',
            'meta_keywords' => 'phone, smartphone',
            'og_image' => 'https://example.com/phone-x-og.jpg',
        ]);
    }

    public function test_product_can_be_created_without_optional_fields(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/products', [
                'sku' => 'SKU-0001',
                'slug' => 'phone-x',
                'title' => ['en' => 'Phone X'],
                'price' => 599.99,
                'stockQuantity' => 25,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.isActive', true);
        $response->assertJsonPath('data.attributes.categoryId', null);
        $response->assertJsonPath('data.attributes.description', []);
        $response->assertJsonPath('data.attributes.image', null);
        $response->assertJsonPath('data.attributes.metaTitle', []);
        $response->assertJsonPath('data.attributes.metaDescription', []);
        $response->assertJsonPath('data.attributes.metaKeywords', null);
        $response->assertJsonPath('data.attributes.ogImage', null);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-0001',
            'slug' => 'phone-x',
            'is_active' => true,
            'category_id' => null,
        ]);
    }

    public function test_slug_must_be_unique(): void
    {
        Product::factory()->create(['slug' => 'phone-x']);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/products', [
                'sku' => 'SKU-0001',
                'slug' => 'phone-x',
                'title' => ['en' => 'Phone X'],
                'price' => 599.99,
                'stockQuantity' => 25,
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/slug');
    }

    public function test_required_fields_are_validated(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/products', []);

        $response->assertUnprocessable();
        $this->assertJsonApiErrorPointers($response, [
            '/data/attributes/sku',
            '/data/attributes/slug',
            '/data/attributes/title',
            '/data/attributes/price',
            '/data/attributes/stockQuantity',
        ]);
    }

    public function test_category_id_must_reference_an_existing_category(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/products', [
                'sku' => 'SKU-0001',
                'slug' => 'phone-x',
                'title' => ['en' => 'Phone X'],
                'price' => 599.99,
                'stockQuantity' => 25,
                'categoryId' => 999999,
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/categoryId');
    }

    public function test_translatable_field_values_must_be_strings(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/products', [
                'sku' => 'SKU-0001',
                'slug' => 'phone-x',
                'title' => ['en' => ['nested' => 'not a string']],
                'price' => 599.99,
                'stockQuantity' => 25,
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/title.en');
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/admin/products', [
            'sku' => 'SKU-0001',
            'slug' => 'phone-x',
            'title' => ['en' => 'Phone X'],
            'price' => 599.99,
            'stockQuantity' => 25,
        ]);

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
