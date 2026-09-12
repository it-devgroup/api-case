<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_browse_the_active_catalog(): void
    {
        Product::factory()->count(2)->create();
        Product::factory()->inactive()->create();

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertJsonPath('data.0.type', 'products');
        $this->assertCount(2, $response->json('data'));
    }
}
