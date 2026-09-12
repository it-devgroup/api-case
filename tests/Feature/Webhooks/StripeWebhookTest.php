<?php

namespace Tests\Feature\Webhooks;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Stripe\WebhookSignature;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::SECRET]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postStripeWebhook(array $payload, ?string $signature = null): TestResponse
    {
        $body = json_encode($payload);
        $signature ??= WebhookSignature::generateSignatureHeader($body, self::SECRET);

        return $this->postJson('/api/webhooks/stripe', $payload, ['Stripe-Signature' => $signature]);
    }

    public function test_valid_checkout_session_completed_marks_order_paid(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending, 'total' => 2000]);

        $response = $this->postStripeWebhook([
            'id' => 'evt_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'object' => 'checkout.session',
                'client_reference_id' => (string) $order->id,
                'payment_intent' => 'pi_123',
                'amount_total' => 2000,
                'currency' => 'nok',
            ]],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Paid->value,
            'stripe_payment_intent_id' => 'pi_123',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => PaymentStatus::Succeeded->value,
            'amount' => 2000,
        ]);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $response = $this->postStripeWebhook([
            'id' => 'evt_2',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test', 'client_reference_id' => (string) $order->id]],
        ], signature: 't=1,v1=deadbeef');

        $response->assertStatus(400);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Pending->value]);
    }

    public function test_replayed_event_is_a_no_op(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending, 'total' => 2000]);

        $payload = [
            'id' => 'evt_3',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'object' => 'checkout.session',
                'client_reference_id' => (string) $order->id,
                'payment_intent' => 'pi_123',
                'amount_total' => 2000,
                'currency' => 'nok',
            ]],
        ];

        $this->postStripeWebhook($payload)->assertOk();
        $this->assertDatabaseCount('payments', 1);

        // Replay the exact same event a second time (Stripe's at-least-once delivery).
        $this->postStripeWebhook($payload)->assertOk();

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('stripe_webhook_events', 1);
    }

    public function test_checkout_session_expired_restocks_items(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3]);
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);

        $response = $this->postStripeWebhook([
            'id' => 'evt_4',
            'object' => 'event',
            'type' => 'checkout.session.expired',
            'data' => ['object' => [
                'id' => 'cs_test_expired',
                'object' => 'checkout.session',
                'client_reference_id' => (string) $order->id,
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Expired->value]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 5]);
    }

    public function test_charge_refunded_marks_payment_and_order_refunded(): void
    {
        $order = Order::factory()->paid()->create();
        Payment::factory()->succeeded()->create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => 'pi_refund_123',
        ]);

        $response = $this->postStripeWebhook([
            'id' => 'evt_5',
            'object' => 'event',
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'id' => 'ch_1',
                'object' => 'charge',
                'payment_intent' => 'pi_refund_123',
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Refunded->value]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'stripe_payment_intent_id' => 'pi_refund_123',
            'status' => PaymentStatus::Refunded->value,
        ]);
    }

    public function test_unknown_event_types_are_accepted_and_ignored(): void
    {
        $response = $this->postStripeWebhook([
            'id' => 'evt_6',
            'object' => 'event',
            'type' => 'customer.created',
            'data' => ['object' => ['id' => 'cus_1', 'object' => 'customer']],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('stripe_webhook_events', ['stripe_event_id' => 'evt_6', 'type' => 'customer.created']);
    }
}
