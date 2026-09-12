<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class OrderItemResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'order-items';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'productId' => $this->resource->product_id,
            'sku' => $this->resource->sku,
            'title' => $this->resource->title,
            'unitPrice' => $this->resource->unit_price,
            'quantity' => $this->resource->quantity,
            'lineTotal' => $this->resource->line_total,
        ];
    }
}
