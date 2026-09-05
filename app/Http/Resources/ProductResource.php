<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ProductResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'products';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'sku' => $this->resource->sku,
            'slug' => $this->resource->slug,
            'title' => $this->resource->getTranslations('title'),
            'description' => $this->resource->getTranslations('description'),
            'is_active' => $this->resource->is_active,
            'category_id' => $this->resource->category_id,
            'price' => $this->resource->price,
            'stock_quantity' => $this->resource->stock_quantity,
            'image' => $this->resource->image,
            'meta_title' => $this->resource->getTranslations('meta_title'),
            'meta_description' => $this->resource->getTranslations('meta_description'),
            'meta_keywords' => $this->resource->meta_keywords,
            'og_image' => $this->resource->og_image,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
