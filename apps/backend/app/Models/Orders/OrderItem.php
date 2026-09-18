<?php

namespace App\Models\Orders;

use App\Models\Products\Product;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line on an order.
 *
 * product_name and unit_price_cents are copies taken at checkout, not lookups.
 * The product relationship is there so the frontend can link back to a still
 * existing item, but nothing about this row depends on it - the order reads
 * correctly even after the product is renamed, repriced or removed.
 */
class OrderItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit_price_cents',
        'quantity',
        'line_total_cents',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
