<?php

namespace App\Http\Resources\Orders;

use App\Enums\OrderStatus;
use App\Http\Resources\BaseResource;
use App\Http\Resources\UserResource;

class OrderResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'order_number' => $this->order_number,

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_final' => $this->status->isFinal(),

            // What this order may still become, so the admin panel can offer
            // exactly those options rather than reimplementing the rules.
            'allowed_transitions' => array_map(
                fn ($status): string => $status->value,
                $this->status->allowedTransitions(),
            ),
            'can_be_cancelled_by_customer' => $this->status->isCancellableByCustomer(),

            // The whole lifecycle, so the interface can show where an order sits
            // in it rather than only the next step. Sent from here for the same
            // reason as allowed_transitions: the stages and their order are the
            // server's to define, and a client that rebuilt the list would be a
            // second copy waiting to drift.
            'step' => $this->status->step(),
            'total_steps' => count(OrderStatus::pipeline()),
            'progress' => array_map(
                fn (OrderStatus $stage): array => [
                    'status' => $stage->value,
                    'label' => $stage->label(),
                    'reached' => $this->status->hasReached($stage),
                    'current' => $stage === $this->status,
                ],
                OrderStatus::pipeline(),
            ),

            'total_cents' => $this->total_cents,
            'total' => round($this->total_cents / 100, 2),

            'delivery_address' => $this->delivery_address,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'placed_at' => $this->placed_at?->toIso8601String(),

            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'customer' => new UserResource($this->whenLoaded('user')),

            ...$this->timestamps(),
        ];
    }
}
