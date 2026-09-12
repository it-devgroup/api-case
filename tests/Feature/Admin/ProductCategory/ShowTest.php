<?php

namespace Tests\Feature\Admin\ProductCategory;

use App\Models\Admin;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_product_category(): void
    {
        $productCategory = ProductCategory::factory()->create([
            'slug' => 'phones',
            'title' => 'Phones',
            'description' => ['en' => 'Shop the latest phones.'],
            'image' => 'https://example.com/phones.jpg',
            'meta_title' => ['en' => 'Phones | Example'],
            'meta_description' => ['en' => 'Browse our phone selection.'],
            'meta_keywords' => 'phones, mobile, smartphones',
            'og_image' => 'https://example.com/phones-og.jpg',
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/product-categories/{$productCategory->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $productCategory->id);
        $response->assertJsonPath('data.attributes.slug', 'phones');
        $response->assertJsonPath('data.attributes.description.en', 'Shop the latest phones.');
        $response->assertJsonPath('data.attributes.image', 'https://example.com/phones.jpg');
        $response->assertJsonPath('data.attributes.metaTitle.en', 'Phones | Example');
        $response->assertJsonPath('data.attributes.metaDescription.en', 'Browse our phone selection.');
        $response->assertJsonPath('data.attributes.metaKeywords', 'phones, mobile, smartphones');
        $response->assertJsonPath('data.attributes.ogImage', 'https://example.com/phones-og.jpg');
    }

    public function test_returns_404_for_missing_product_category(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/product-categories/999999');

        $response->assertNotFound();
        $this->assertJsonApiError($response, '404');
    }

    public function test_show_requires_authentication(): void
    {
        $productCategory = ProductCategory::factory()->create();

        $response = $this->getJson("/api/admin/product-categories/{$productCategory->id}");

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }
}
