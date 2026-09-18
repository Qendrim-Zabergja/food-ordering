import { api } from '../lib/api'
import { Cart } from '../models/Cart'
import type { ApiPayload } from '../models/Model'

/**
 * Every one of these returns the whole cart, because that is what the API
 * answers with. The interface never has to recalculate a total after a change -
 * it renders the one the server just sent.
 */
export async function getCart(): Promise<Cart> {
  const { data } = await api.get<ApiPayload>('/cart')

  return new Cart().hydrate(data)
}

export async function addToCart(productId: string, quantity = 1): Promise<Cart> {
  const { data } = await api.post<ApiPayload>('/cart/items', {
    product: { id: productId },
    quantity,
  })

  return new Cart().hydrate(data)
}

export async function updateCartItem(cartItemId: string, quantity: number): Promise<Cart> {
  const { data } = await api.patch<ApiPayload>(`/cart/items/${cartItemId}`, { quantity })

  return new Cart().hydrate(data)
}

export async function removeCartItem(cartItemId: string): Promise<Cart> {
  const { data } = await api.delete<ApiPayload>(`/cart/items/${cartItemId}`)

  return new Cart().hydrate(data)
}

export async function clearCart(): Promise<Cart> {
  const { data } = await api.delete<ApiPayload>('/cart')

  return new Cart().hydrate(data)
}
