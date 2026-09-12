<?php

namespace App\Contracts;

use App\Models\Order;
use App\Support\Payments\CheckoutSession;

interface PaymentGateway
{
    public function createCheckoutSession(Order $order): CheckoutSession;
}
