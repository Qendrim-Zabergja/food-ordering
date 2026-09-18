<?php

namespace App\Http\Resources\Orders;

use App\Http\Resources\BaseResource;
use App\Http\Resources\Products\ProductResource;

class OrderItemResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,

            // Read from this row, not from the product: this is what was sold,
            // at the price it was sold for.
            'product_name' => $this->product_name,
            'unit_price_cents' => $this->unit_price_cents,
            'unit_price' => round($this->unit_price_cents / 100, 2),
            'quantity' => $this->quantity,
            'line_total_cents' => $this->line_total_cents,
            'line_total' => round($this->line_total_cents / 100, 2),

            // Null once the product has been removed from the menu. The line
            // above still reads correctly.
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
