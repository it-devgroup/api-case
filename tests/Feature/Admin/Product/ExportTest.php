<?php

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_products_to_xlsx(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-0001',
            'slug' => 'phone-x',
            'title' => ['en' => 'Phone X'],
            'is_active' => false,
            'stock_quantity' => 25,
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/admin/products/export');

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

        $this->assertSame('SKU-0001', $row['sku']);
        $this->assertSame('phone-x', $row['slug']);
        $this->assertSame(['en' => 'Phone X'], json_decode($row['title'], true));
        $this->assertSame(0, (int) $row['isActive']);
        $this->assertSame(25, (int) $row['stockQuantity']);
        $this->assertSame($product->price, (float) $row['price']);
    }

    public function test_export_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/products/export');

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }

    public function test_user_token_cannot_export_products(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/products/export');

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
    }
}
