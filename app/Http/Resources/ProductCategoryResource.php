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
            'meta_title' => $this->resource->getTranslations('meta_title'),
            'meta_description' => $this->resource->getTranslations('meta_description'),
            'meta_keywords' => $this->resource->meta_keywords,
            'og_image' => $this->resource->og_image,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
