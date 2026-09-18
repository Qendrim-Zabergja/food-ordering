<?php

namespace App\Http\Resources\Products;

use App\Http\Resources\BaseResource;

class ProductResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,

            // price is the display value; price_cents is what the server totals
            // orders with, and both are sent so the client never has to convert.
            'price' => $this->price,
            'price_cents' => $this->price_cents,

            'image_url' => $this->image_url,
            'is_available' => $this->is_available,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            ...$this->timestamps(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
