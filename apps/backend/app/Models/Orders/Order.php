<?php

namespace App\Models\Orders;

use App\Enums\OrderStatus;
use App\Filters\Filterable;
use App\Models\User;
use App\Policies\Orders\OrderPolicy;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[UsePolicy(OrderPolicy::class)]
class Order extends Model
{
    use Filterable, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'total_cents',
        'delivery_address',
        'phone',
        'notes',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_cents' => 'integer',
            'placed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Moves the order to a new status, or refuses.
     *
     * The rule lives on the enum, so every caller - controller, console command,
     * future webhook - gets the same answer. Refusing with a ValidationException
     * rather than a boolean means an invalid transition reaches the client as a
     * 422 explaining what was and was not possible.
     */
    public function transitionTo(OrderStatus $status): self
    {
        if (! $this->status->canTransitionTo($status)) {
            $allowed = array_map(
                fn (OrderStatus $option): string => $option->value,
                $this->status->allowedTransitions(),
            );

            throw ValidationException::withMessages([
                'status' => $allowed === []
                    ? "This order is {$this->status->value} and can no longer change."
                    : "An order that is {$this->status->value} can only move to: ".implode(', ', $allowed).'.',
            ]);
        }

        $this->update(['status' => $status]);

        return $this;
    }

    /**
     * ORD-20260918-0001
     *
     * Generated inside the checkout transaction so the daily sequence cannot be
     * handed out twice; the unique index on the column is the backstop.
     */
    public static function nextOrderNumber(): string
    {
        $today = now()->format('Ymd');

        $sequence = static::withTrashed()
            ->where('order_number', 'LIKE', "ORD-{$today}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('ORD-%s-%04d', $today, $sequence);
    }
}
