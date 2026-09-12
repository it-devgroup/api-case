<?php

namespace Tests\Feature\Admin\ProductCategory;

use App\Models\Admin;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_product_categories_to_xlsx(): void
    {
        ProductCategory::factory()->create([
            'slug' => 'phones',
            'title' => 'Phones',
            'description' => ['en' => 'Shop the latest phones.'],
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/admin/product-categories/export');

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $rows = (new FastExcel)->import($path);
        unlink($path);

        $this->assertCount(1, $rows);
        $row = $rows->first();

        $this->assertSame('phones', $row['slug']);
        $this->assertSame('Phones', $row['title']);
        $this->assertSame(['en' => 'Shop the latest phones.'], json_decode($row['description'], true));
    }

    public function test_export_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/product-categories/export');

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }

    public function test_user_token_cannot_export_product_categories(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/product-categories/export');

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
    }
}
