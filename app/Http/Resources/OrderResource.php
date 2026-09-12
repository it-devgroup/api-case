<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class OrderResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'orders';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'userId' => $this->resource->user_id,
            'status' => $this->resource->status,
            'currency' => $this->resource->currency,
            'subtotal' => $this->resource->subtotal,
            'total' => $this->resource->total,
            'stripeCheckoutSessionId' => $this->resource->stripe_checkout_session_id,
            'stripePaymentIntentId' => $this->resource->stripe_payment_intent_id,
            'paidAt' => $this->resource->paid_at,
            'cancelledAt' => $this->resource->cancelled_at,
            'expiresAt' => $this->resource->expires_at,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
