<?php

namespace App\Http\Controllers\Carts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Carts\StoreCartItemRequest;
use App\Http\Requests\Carts\UpdateCartItemRequest;
use App\Http\Resources\Carts\CartResource;
use App\Models\Carts\Cart;
use App\Models\Carts\CartItem;
use App\Models\Products\Product;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CartItemController extends Controller
{
    /**
     * Adds a product to the cart, or raises its quantity if it is already there.
     *
     * The request carries a product uuid and a quantity, and nothing else. Price
     * is read from the product row on the server - a client that posts a price
     * is ignored, which is the whole reason the cart lives in the database
     * rather than in the browser.
     */
    public function store(StoreCartItemRequest $request): Response
    {
        $cart = Cart::forUser($request->user());

        $this->authorize('update', $cart);

        $validated = $request->validated();
        $product = Product::where('uuid', $validated['product']['id'])->firstOrFail();

        if (! $product->is_available) {
            throw ValidationException::withMessages([
                'product.id' => 'This product is not available at the moment.',
            ]);
        }

        $quantity = $validated['quantity'] ?? 1;

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $item->update(['quantity' => min($item->quantity + $quantity, 99)]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }

        return response(new CartResource($cart->load(['items.product'])), 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): Response
    {
        $this->authorize('update', $cartItem);

        $cartItem->update($request->validated());

        return response(new CartResource($cartItem->cart->load(['items.product'])), 200);
    }

    public function destroy(CartItem $cartItem): Response
    {
        $this->authorize('delete', $cartItem);

        $cart = $cartItem->cart;
        $cartItem->delete();

        return response(new CartResource($cart->load(['items.product'])), 200);
    }
}
