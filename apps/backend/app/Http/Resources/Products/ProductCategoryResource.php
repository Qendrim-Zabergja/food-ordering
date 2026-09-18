<?php

namespace App\Http\Resources\Products;

use App\Http\Resources\BaseResource;

class ProductCategoryResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'products_count' => $this->whenCounted('products'),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            ...$this->timestamps(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
