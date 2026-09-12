<?php

namespace App\Support\Payments;

final class CheckoutSession
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
    ) {}
}
