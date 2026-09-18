<?php

namespace App\Enums;

/**
 * The lifecycle of an order.
 *
 * A backed enum rather than a lookup table: the set is fixed in code, is never
 * edited by a user, and carries the transition rules below - behaviour a
 * database row could not express. See CLAUDE.md, deviation 3.
 *
 *   pending → confirmed → preparing → delivering → completed
 *      └──────────┴───────────┴───────────┘
 *                    ↓
 *                cancelled
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case DELIVERING = 'delivering';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::PREPARING => 'Preparing',
            self::DELIVERING => 'Out for delivery',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * The statuses this one may move to.
     *
     * Progress is strictly forward: an order cannot go back to preparing once it
     * is out for delivery, because the thing that step describes has already
     * happened in the real world. Cancellation is available until the food is
     * on its way out.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::CONFIRMED, self::PREPARING, self::CANCELLED],
            self::CONFIRMED => [self::PREPARING, self::CANCELLED],
            self::PREPARING => [self::DELIVERING, self::CANCELLED],
            self::DELIVERING => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED, self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * A final status is the end of the line - nothing follows it.
     */
    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Whether a customer may still cancel an order in this status themselves.
     *
     * Narrower than what an administrator can do: once the kitchen has started
     * cooking, cancelling is a conversation, not a button.
     */
    public function isCancellableByCustomer(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
