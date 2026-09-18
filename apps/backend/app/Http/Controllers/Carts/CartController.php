<?php

namespace App\Http\Controllers\Carts;

use App\Http\Controllers\Controller;
use App\Http\Resources\Carts\CartResource;
use App\Models\Carts\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    /**
     * The current user's cart. There is no /carts/{id} endpoint: a user has
     * exactly one cart and never addresses anyone else's, so the route carries
     * no identifier and nothing can be enumerated.
     */
    public function show(Request $request): Response
    {
        $cart = Cart::forUser($request->user());

        $this->authorize('view', $cart);

        return response(new CartResource($this->withItems($cart)), 200);
    }

    /**
     * Empties the cart without deleting it.
     */
    public function destroy(Request $request): Response
    {
        $cart = Cart::forUser($request->user());

        $this->authorize('update', $cart);

        $cart->items()->delete();

        return response(new CartResource($this->withItems($cart->fresh())), 200);
    }

    /**
     * Items and their products are always loaded, not left to the `with` query
     * parameter: the cart's totals are computed from them, so a cart serialised
     * without them would report a subtotal of zero.
     */
    protected function withItems(Cart $cart): Cart
    {
        return $cart->load(['items.product']);
    }
}
