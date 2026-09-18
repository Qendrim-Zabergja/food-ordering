<?php

namespace App\Policies\Orders;

use App\Enums\PermissionSlug;
use App\Models\Orders\Order;
use App\Models\User;

/**
 * Orders combine both kinds of check this application uses.
 *
 * The permission decides whether you can reach the feature at all; ownership
 * decides which rows you see once you are there. A customer holds view-orders
 * and sees their own; an administrator holds manage-orders and sees every one.
 * The scoping itself is applied in OrderController::index.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissions([PermissionSlug::VIEW_ORDERS]);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->administers($user) || $order->user_id === $user->id;
    }

    /**
     * Any authenticated user may place their own order. There is no permission
     * for this: an order belongs to the person making it, so ownership is the
     * only meaningful rule.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Moving an order through its lifecycle is the restaurant's job.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $this->administers($user);
    }

    /**
     * A customer may call off their own order while it is still early enough;
     * an administrator may cancel any order the status rules still allow.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($this->administers($user)) {
            return true;
        }

        return $order->user_id === $user->id
            && $order->status->isCancellableByCustomer();
    }

    protected function administers(User $user): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_ORDERS]);
    }
}
