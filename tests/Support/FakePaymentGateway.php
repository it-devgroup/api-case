<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Support\Payments\CheckoutSession;

class FakePaymentGateway implements PaymentGateway
{
    /**
     * @var array<int, Order>
     */
    public array $orders = [];

    public function __construct(
        private readonly string $sessionId = 'cs_test_fake123',
        private readonly string $url = 'https://checkout.stripe.com/pay/cs_test_fake123',
    ) {}

    public function createCheckoutSession(Order $order): CheckoutSession
    {
        $this->orders[] = $order;

        return new CheckoutSession($this->sessionId, $this->url);
    }
}
