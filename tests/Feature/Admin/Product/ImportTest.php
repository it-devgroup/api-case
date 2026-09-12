<?php

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_products_from_a_spreadsheet(): void
    {
        $existing = Product::factory()->create([
            'sku' => 'SKU-OLD',
            'slug' => 'existing-product',
            'price' => 10,
            'stock_quantity' => 5,
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $csv = $this->csv([
            [
                'sku' => 'SKU-OLD',
                'slug' => 'existing-product',
                'title' => json_encode(['en' => 'Existing Product']),
                'description' => '',
                'isActive' => 1,
                'categoryId' => '',
                'price' => 15,
                'stockQuantity' => 8,
                'image' => '',
                'metaTitle' => '',
                'metaDescription' => '',
                'metaKeywords' => '',
                'ogImage' => '',
            ],
            [
                'sku' => 'SKU-NEW',
                'slug' => 'new-product',
                'title' => json_encode(['en' => 'New Product']),
                'description' => '',
                'isActive' => 1,
                'categoryId' => '',
                'price' => 20,
                'stockQuantity' => 3,
                'image' => '',
                'metaTitle' => '',
                'metaDescription' => '',
                'metaKeywords' => '',
                'ogImage' => '',
            ],
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/admin/products/import', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ]);

        $response->assertOk();
        $response->assertJsonPath('meta.created', 1);
        $response->assertJsonPath('meta.updated', 1);
        $response->assertJsonPath('meta.failed', 0);

        $this->assertDatabaseHas('products', [
            'id' => $existing->id,
            'price' => 15,
            'stock_quantity' => 8,
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-NEW',
            'slug' => 'new-product',
            'price' => 20,
            'stock_quantity' => 3,
        ]);

        $product = Product::where('slug', 'new-product')->first();
        $this->assertSame('New Product', $product->getTranslation('title', 'en'));
    }

    public function test_invalid_rows_are_reported_and_skipped(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $csv = $this->csv([
            [
                'sku' => 'SKU-BAD',
                'slug' => '',
                'title' => json_encode(['en' => 'Missing slug']),
                'description' => '',
                'isActive' => 1,
                'categoryId' => '',
                'price' => 5,
                'stockQuantity' => 1,
                'image' => '',
                'metaTitle' => '',
                'metaDescription' => '',
                'metaKeywords' => '',
                'ogImage' => '',
            ],
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/admin/products/import', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ]);

        $response->assertOk();
        $response->assertJsonPath('meta.created', 0);
        $response->assertJsonPath('meta.failed', 1);
        $response->assertJsonPath('meta.errors.0.row', 2);
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-BAD']);
    }

    public function test_import_requires_a_file(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/products/import', []);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/file');
    }

    public function test_import_requires_authentication(): void
    {
        $response = $this->postJson('/api/admin/products/import', []);

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }

    public function test_user_token_cannot_import_products(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/products/import', []);

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'w+');

        fputcsv($handle, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }
}
