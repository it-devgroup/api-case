<?php

namespace Tests\Feature\ProductCategory;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_browse_categories(): void
    {
        ProductCategory::factory()->count(2)->create();

        $response = $this->getJson('/api/product-categories');

        $response->assertOk();
        $response->assertJsonPath('data.0.type', 'product-categories');
        $this->assertCount(2, $response->json('data'));
    }
}
