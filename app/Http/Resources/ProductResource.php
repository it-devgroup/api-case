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
            'isActive' => $this->resource->is_active,
            'categoryId' => $this->resource->category_id,
            'price' => $this->resource->price,
            'stockQuantity' => $this->resource->stock_quantity,
            'image' => $this->resource->image,
            'metaTitle' => $this->resource->getTranslations('meta_title'),
            'metaDescription' => $this->resource->getTranslations('meta_description'),
            'metaKeywords' => $this->resource->meta_keywords,
            'ogImage' => $this->resource->og_image,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}
