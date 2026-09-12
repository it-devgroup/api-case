<?php

namespace Tests\Feature\Admin\ProductCategory;

use App\Models\Admin;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_product_categories_from_a_spreadsheet(): void
    {
        $existing = ProductCategory::factory()->create([
            'slug' => 'phones',
            'title' => 'Phones',
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $csv = $this->csv([
            [
                'slug' => 'phones',
                'title' => 'Mobile Phones',
                'description' => json_encode(['en' => 'Shop the latest phones.']),
                'image' => '',
                'metaTitle' => '',
                'metaDescription' => '',
                'metaKeywords' => '',
                'ogImage' => '',
            ],
            [
                'slug' => 'tablets',
                'title' => 'Tablets',
                'description' => json_encode(['en' => 'Shop the latest tablets.']),
                'image' => '',
                'metaTitle' => '',
                'metaDescription' => '',
                'metaKeywords' => '',
                'ogImage' => '',
            ],
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/admin/product-categories/import', [
                'file' => UploadedFile::fake()->createWithContent('product-categories.csv', $csv),
            ]);

        $response->assertOk();
        $response->assertJsonPath('meta.created', 1);
        $response->assertJsonPath('meta.updated', 1);
        $response->assertJsonPath('meta.failed', 0);

        $this->assertDatabaseHas('product_categories', [
            'id' => $existing->id,
            'title' => 'Mobile Phones',
        ]);
        $this->assertDatabaseHas('product_categories', [
            'slug' => 'tablets',
            'title' => 'Tablets',
        ]);

        $productCategory = ProductCategory::where('slug', 'tablets')->first();
        $this->assertSame('Shop the latest tablets.', $productCategory->getTranslation('description', 'en'));
    }

    public function test_invalid_rows_are_reported_and_skipped(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $csv = $this->csv([
            [
                'slug' => '',
                'title' => 'Missing slug',
                'description' => '',
                'image' => '',
                'metaTitle' => '',
                'metaDescription' => '',
                'metaKeywords' => '',
                'ogImage' => '',
            ],
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/admin/product-categories/import', [
                'file' => UploadedFile::fake()->createWithContent('product-categories.csv', $csv),
            ]);

        $response->assertOk();
        $response->assertJsonPath('meta.created', 0);
        $response->assertJsonPath('meta.failed', 1);
        $response->assertJsonPath('meta.errors.0.row', 2);
        $this->assertDatabaseMissing('product_categories', ['title' => 'Missing slug']);
    }

    public function test_import_requires_a_file(): void
    {
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/product-categories/import', []);

        $response->assertUnprocessable();
        $this->assertJsonApiError($response, '422', '/data/attributes/file');
    }

    public function test_import_requires_authentication(): void
    {
        $response = $this->postJson('/api/admin/product-categories/import', []);

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }

    public function test_user_token_cannot_import_product_categories(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/product-categories/import', []);

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
