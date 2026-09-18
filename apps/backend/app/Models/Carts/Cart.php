<?php

namespace App\Models\Carts;

use App\Models\User;
use App\Policies\Carts\CartPolicy;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A user's cart. Exactly one per user, created on first use.
 *
 * No SoftDeletes: a cart is working state, not a record. Placing an order clears
 * its items, and there is nothing meaningful to restore afterwards - the order
 * is the permanent record of what was bought.
 */
#[UsePolicy(CartPolicy::class)]
class Cart extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Returns the user's cart, creating it the first time they use one.
     */
    public static function forUser(User $user): self
    {
        return static::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * The cart total, in cents, from the products' current prices.
     *
     * Deliberately computed rather than stored. A cart that remembered the price
     * from when an item was added would quietly charge a stale price; the order
     * is where a price gets captured, at the moment it is agreed.
     */
    public function subtotalCents(): int
    {
        return $this->items->sum(fn (CartItem $item): int => $item->lineTotalCents());
    }

    public function itemsCount(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
