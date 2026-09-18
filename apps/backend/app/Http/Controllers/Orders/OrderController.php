<?php

namespace App\Http\Controllers\Orders;

use App\Enums\OrderStatus;
use App\Enums\PermissionSlug;
use App\Filters\OrderFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderStatusRequest;
use App\Http\Resources\Orders\OrderResource;
use App\Models\Orders\Order;
use App\Services\Orders\OrderCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function __construct(protected OrderCheckoutService $checkout) {}

    /**
     * A customer's own orders, or every order for an administrator.
     *
     * The permission opens the endpoint; this scope decides the rows. Without
     * it, view-orders would show a customer everyone else's orders, which is
     * the kind of mistake a permission check alone does not catch.
     */
    public function index(Request $request, OrderFilters $filters): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::filter($filters)->withCount('items');

        if (! $request->user()->hasPermissions([PermissionSlug::MANAGE_ORDERS])) {
            $orders->where('user_id', $request->user()->id);
        }

        return OrderResource::collection(
            $orders->paginate($request->integer('limit', 15))
        )->response();
    }

    public function show(Order $order, Request $request): Response
    {
        $this->authorize('view', $order);

        $order = $this->loadRelationships($order->load('items'), $request);

        return response(new OrderResource($order), 200);
    }

    /**
     * Checkout. The body carries delivery details only - the items, their
     * prices and the total all come from the user's cart on the server.
     */
    public function store(StoreOrderRequest $request): Response
    {
        $this->authorize('create', Order::class);

        $order = $this->checkout->placeOrder($request->user(), $request->validated());

        $order = $this->loadRelationships($order, $request);

        return response(new OrderResource($order), 201);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): Response
    {
        $this->authorize('updateStatus', $order);

        $order->transitionTo(OrderStatus::from($request->validated()['status']));

        $order = $this->loadRelationships($order->load('items'), $request);

        return response(new OrderResource($order), 200);
    }

    /**
     * Cancelling is its own endpoint rather than a status update, because who
     * may do it differs: a customer can call off their own order early on, an
     * administrator can cancel any order the status rules still permit.
     */
    public function cancel(Order $order, Request $request): Response
    {
        $this->authorize('cancel', $order);

        $order->transitionTo(OrderStatus::CANCELLED);

        $order = $this->loadRelationships($order->load('items'), $request);

        return response(new OrderResource($order), 200);
    }
}
