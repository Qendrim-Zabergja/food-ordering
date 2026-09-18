<?php

namespace App\Policies\Carts;

use App\Models\Carts\Cart;
use App\Models\User;

/**
 * A cart is not permission-gated: every authenticated user has one, and no
 * permission would ever grant access to someone else's. The rule here is
 * ownership, which is why these checks compare user ids instead of calling
 * hasPermissions().
 */
class CartPolicy
{
    public function view(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id;
    }

    public function update(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id;
    }
}
