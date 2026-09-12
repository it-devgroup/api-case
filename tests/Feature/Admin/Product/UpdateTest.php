<?php

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_partially_update_a_product(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'slug' => 'phone-x',
            'price' => 599.99,
            'stock_quantity' => 25,
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/products/{$product->id}", [
                'price' => 499.99,
                'stockQuantity' => 10,
                'isActive' => false,
                'categoryId' => $category->id,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.price', 499.99);
        $response->assertJsonPath('data.attributes.stockQuantity', 10);
        $response->assertJsonPath('data.attributes.isActive', false);
        $response->assertJsonPath('data.attributes.categoryId', $category->id);
        $response->assertJsonPath('data.attributes.slug', 'phone-x');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 499.99,
            'stock_quantity' => 10,
            'is_active' => false,
            'category_id' => $category->id,
        ]);
    }

    public function test_updating_a_translatable_locale_preserves_other_locales(): void
    {
        $product = Product::factory()->create([
            'title' => ['en' => 'Phone X'],
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/products/{$product->id}", [
                'title' => ['no' => 'Telefon X'],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.title.en', 'Phone X');
        $response->assertJsonPath('data.attributes.title.no', 'Telefon X');
    }

    public function test_slug_uniqueness_ignores_the_current_record(): void
    {
        $product = Product::factory()->create(['slug' => 'phone-x']);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/products/{$product->id}", [
                'slug' => 'phone-x',
            ]);

        $response->assertOk();
    }

    public function test_slug_must_be_unique_among_other_records(): void
    {
        Product::factory()->create(['slug' => 'phone-x']);
        $product = Product::factory()->create(['slug' => 'tablet-y']);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/products/{$product->id}", [
                'slug' => 'phone-x',
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/slug');
    }

    public function test_category_id_must_reference_an_existing_category(): void
    {
        $product = Product::factory()->create();
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/products/{$product->id}", [
                'categoryId' => 999999,
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/categoryId');
    }

    public function test_update_requires_authentication(): void
    {
        $product = Product::factory()->create();

        $response = $this->patchJson("/api/admin/products/{$product->id}", [
            'price' => 100,
        ]);

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
