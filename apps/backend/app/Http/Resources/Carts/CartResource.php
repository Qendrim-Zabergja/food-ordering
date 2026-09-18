<?php

namespace App\Http\Resources\Carts;

use App\Http\Resources\BaseResource;

class CartResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->itemsCount(),
            'subtotal_cents' => $this->subtotalCents(),
            'subtotal' => round($this->subtotalCents() / 100, 2),
            ...$this->timestamps(),
        ];
    }
}
