<?php

namespace Tests\Feature\Admin\Order;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_orders_to_xlsx(): void
    {
        $order = Order::factory()->paid()->create([
            'currency' => 'nok',
            'subtotal' => 2000,
            'total' => 2000,
        ]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/admin/orders/export');

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

        $this->assertSame((string) $order->id, (string) $row['id']);
        $this->assertSame($order->user_id, (int) $row['userId']);
        $this->assertSame('paid', $row['status']);
        $this->assertSame('nok', $row['currency']);
        $this->assertSame(2000, (int) $row['subtotal']);
        $this->assertSame(2000, (int) $row['total']);
        $this->assertNotEmpty($row['paidAt']);
    }

    public function test_export_can_be_filtered_by_status(): void
    {
        Order::factory()->paid()->create();
        Order::factory()->create(['status' => OrderStatus::Pending]);
        $token = Admin::factory()->create()->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/admin/orders/export?status=paid');

        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $rows = (new FastExcel)->import($path);
        unlink($path);

        $this->assertCount(1, $rows);
        $this->assertSame('paid', $rows->first()['status']);
    }

    public function test_export_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/orders/export');

        $response->assertUnauthorized();
        $this->assertJsonApiError($response, '401');
    }

    public function test_user_token_cannot_export_orders(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/orders/export');

        $response->assertForbidden();
        $this->assertJsonApiError($response, '403');
    }
}
