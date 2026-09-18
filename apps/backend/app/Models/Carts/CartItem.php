<?php

namespace App\Models\Carts;

use App\Models\Products\Product;
use App\Policies\Carts\CartItemPolicy;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(CartItemPolicy::class)]
class CartItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['cart_id', 'product_id', 'quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotalCents(): int
    {
        return $this->quantity * ($this->product?->price_cents ?? 0);
    }
}
