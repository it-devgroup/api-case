<?php

namespace App\Services\Stripe;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Payments\CheckoutSession;
use Stripe\StripeClient;

final class StripeCheckoutGateway implements PaymentGateway
{
    public function __construct(private readonly StripeClient $client) {}

    public function createCheckoutSession(Order $order): CheckoutSession
    {
        $session = $this->client->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => (string) $order->id,
            'metadata' => ['order_id' => (string) $order->id],
            'expires_at' => $order->expires_at?->getTimestamp(),
            'success_url' => config('services.stripe.checkout_success_url'),
            'cancel_url' => config('services.stripe.checkout_cancel_url'),
            'line_items' => $order->items->map(fn (OrderItem $item) => [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => $order->currency,
                    'unit_amount' => $item->unit_price,
                    'product_data' => [
                        'name' => $item->title,
                    ],
                ],
            ])->all(),
        ]);

        return new CheckoutSession($session->id, $session->url);
    }
}
