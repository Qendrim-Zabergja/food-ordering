<?php

namespace App\Http\Resources\Carts;

use App\Http\Resources\BaseResource;
use App\Http\Resources\Products\ProductResource;

class CartItemResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'quantity' => $this->quantity,

            // The unit price is the product's current price, not a value the
            // client sent or a figure captured when the item was added.
            'unit_price_cents' => $this->product?->price_cents,
            'unit_price' => $this->product?->price,
            'line_total_cents' => $this->lineTotalCents(),
            'line_total' => round($this->lineTotalCents() / 100, 2),

            'product' => new ProductResource($this->whenLoaded('product')),
            ...$this->timestamps(),
        ];
    }
}
