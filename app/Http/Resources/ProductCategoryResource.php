<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ProductCategoryResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'product-categories';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'slug' => $this->resource->slug,
            'title' => $this->resource->title,
            'description' => $this->resource->getTranslations('description'),
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
