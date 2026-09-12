<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_an_active_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.sku', $product->sku);
    }

    public function test_inactive_products_are_not_publicly_visible(): void
    {
        $product = Product::factory()->inactive()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertNotFound();
    }
}
