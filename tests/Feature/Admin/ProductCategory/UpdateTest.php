<?php

namespace Tests\Feature\Admin\ProductCategory;

use App\Models\Admin;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_partially_update_a_product_category(): void
    {
        $productCategory = ProductCategory::factory()->create([
            'slug' => 'phones',
            'title' => 'Phones',
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/product-categories/{$productCategory->id}", [
                'title' => 'Mobile Phones',
                'image' => 'https://example.com/mobile-phones.jpg',
                'metaKeywords' => 'mobile phones, smartphones',
                'ogImage' => 'https://example.com/mobile-phones-og.jpg',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.title', 'Mobile Phones');
        $response->assertJsonPath('data.attributes.slug', 'phones');
        $response->assertJsonPath('data.attributes.image', 'https://example.com/mobile-phones.jpg');
        $response->assertJsonPath('data.attributes.metaKeywords', 'mobile phones, smartphones');
        $response->assertJsonPath('data.attributes.ogImage', 'https://example.com/mobile-phones-og.jpg');

        $this->assertDatabaseHas('product_categories', ['id' => $productCategory->id, 'title' => 'Mobile Phones']);
    }

    public function test_updating_a_translatable_locale_preserves_other_locales(): void
    {
        $productCategory = ProductCategory::factory()->create([
            'description' => ['en' => 'Shop the latest phones.'],
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/product-categories/{$productCategory->id}", [
                'description' => ['no' => 'Kjøp de nyeste telefonene.'],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.description.en', 'Shop the latest phones.');
        $response->assertJsonPath('data.attributes.description.no', 'Kjøp de nyeste telefonene.');
    }

    public function test_slug_uniqueness_ignores_the_current_record(): void
    {
        $productCategory = ProductCategory::factory()->create(['slug' => 'phones']);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/product-categories/{$productCategory->id}", [
                'slug' => 'phones',
            ]);

        $response->assertOk();
    }

    public function test_slug_must_be_unique_among_other_records(): void
    {
        ProductCategory::factory()->create(['slug' => 'phones']);
        $productCategory = ProductCategory::factory()->create(['slug' => 'tablets']);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/product-categories/{$productCategory->id}", [
                'slug' => 'phones',
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/slug');
    }

    public function test_update_requires_authentication(): void
    {
        $productCategory = ProductCategory::factory()->create();

        $response = $this->patchJson("/api/admin/product-categories/{$productCategory->id}", [
            'title' => 'Mobile Phones',
        ]);

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
