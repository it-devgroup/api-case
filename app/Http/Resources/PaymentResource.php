<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class PaymentResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'payments';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'orderId' => $this->resource->order_id,
            'provider' => $this->resource->provider,
            'stripeCheckoutSessionId' => $this->resource->stripe_checkout_session_id,
            'stripePaymentIntentId' => $this->resource->stripe_payment_intent_id,
            'status' => $this->resource->status,
            'amount' => $this->resource->amount,
            'currency' => $this->resource->currency,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}
