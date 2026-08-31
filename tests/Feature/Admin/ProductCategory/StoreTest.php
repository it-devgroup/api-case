<?php

namespace Tests\Feature\Admin\ProductCategory;

use App\Models\Admin;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_product_category(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/product-categories', [
                'slug' => 'phones',
                'title' => 'Phones',
                'description' => ['en' => 'Shop the latest phones.', 'no' => 'Kjøp de nyeste telefonene.'],
                'image' => 'https://example.com/phones.jpg',
                'meta_title' => ['en' => 'Phones | Example'],
                'meta_description' => ['en' => 'Browse our phone selection.'],
                'meta_keywords' => 'phones, mobile, smartphones',
                'og_image' => 'https://example.com/phones-og.jpg',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'product-categories');
        $response->assertJsonPath('data.attributes.slug', 'phones');
        $response->assertJsonPath('data.attributes.title', 'Phones');
        $response->assertJsonPath('data.attributes.description.en', 'Shop the latest phones.');
        $response->assertJsonPath('data.attributes.description.no', 'Kjøp de nyeste telefonene.');
        $response->assertJsonPath('data.attributes.image', 'https://example.com/phones.jpg');
        $response->assertJsonPath('data.attributes.meta_title.en', 'Phones | Example');
        $response->assertJsonPath('data.attributes.meta_description.en', 'Browse our phone selection.');
        $response->assertJsonPath('data.attributes.meta_keywords', 'phones, mobile, smartphones');
        $response->assertJsonPath('data.attributes.og_image', 'https://example.com/phones-og.jpg');

        $this->assertDatabaseHas('product_categories', [
            'slug' => 'phones',
            'title' => 'Phones',
            'image' => 'https://example.com/phones.jpg',
            'meta_keywords' => 'phones, mobile, smartphones',
            'og_image' => 'https://example.com/phones-og.jpg',
        ]);
    }

    public function test_slug_must_be_unique(): void
    {
        ProductCategory::factory()->create(['slug' => 'phones']);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/product-categories', [
                'slug' => 'phones',
                'title' => 'Phones',
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/slug');
    }

    public function test_required_fields_are_validated(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/product-categories', []);

        $response->assertUnprocessable();
        $this->assertJsonApiErrorPointers($response, [
            '/data/attributes/slug',
            '/data/attributes/title',
        ]);
    }

    public function test_product_category_can_be_created_without_optional_fields(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/product-categories', [
                'slug' => 'phones',
                'title' => 'Phones',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.description', []);
        $response->assertJsonPath('data.attributes.image', null);
        $response->assertJsonPath('data.attributes.meta_title', []);
        $response->assertJsonPath('data.attributes.meta_description', []);
        $response->assertJsonPath('data.attributes.meta_keywords', null);
        $response->assertJsonPath('data.attributes.og_image', null);

        $this->assertDatabaseHas('product_categories', [
            'slug' => 'phones',
            'description' => null,
            'image' => null,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'og_image' => null,
        ]);
    }

    public function test_translatable_field_values_must_be_strings(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/product-categories', [
                'slug' => 'phones',
                'title' => 'Phones',
                'description' => ['en' => ['nested' => 'not a string']],
            ]);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/description.en');
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/admin/product-categories', [
            'slug' => 'phones',
            'title' => 'Phones',
        ]);

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
