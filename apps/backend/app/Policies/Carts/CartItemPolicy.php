<?php

namespace App\Policies\Carts;

use App\Models\Carts\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function view(User $user, CartItem $cartItem): bool
    {
        return $this->owns($user, $cartItem);
    }

    public function update(User $user, CartItem $cartItem): bool
    {
        return $this->owns($user, $cartItem);
    }

    public function delete(User $user, CartItem $cartItem): bool
    {
        return $this->owns($user, $cartItem);
    }

    /**
     * Loaded through the relationship rather than a join so that an item whose
     * cart is missing fails closed.
     */
    protected function owns(User $user, CartItem $cartItem): bool
    {
        return $cartItem->cart?->user_id === $user->id;
    }
}
