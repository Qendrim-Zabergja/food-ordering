import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { addToCart, clearCart, getCart, removeCartItem, updateCartItem } from '../api/cart'
import { Cart } from '../models/Cart'
import { queryKeys } from './queryKeys'

export function useCart(enabled = true) {
  return useQuery({
    queryKey: queryKeys.cart(),
    queryFn: getCart,
    enabled,
  })
}

/**
 * Every cart mutation answers with the whole cart, so the result is written
 * straight into the cache. No refetch, and no chance of the badge in the header
 * disagreeing with the page.
 */
function useCartMutation<TVariables>(mutationFn: (variables: TVariables) => Promise<Cart>) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn,
    onSuccess: (cart) => queryClient.setQueryData(queryKeys.cart(), cart),
  })
}

export function useAddToCart() {
  return useCartMutation(({ productId, quantity }: { productId: string; quantity?: number }) =>
    addToCart(productId, quantity),
  )
}

export function useUpdateCartItem() {
  return useCartMutation(({ id, quantity }: { id: string; quantity: number }) =>
    updateCartItem(id, quantity),
  )
}

export function useRemoveCartItem() {
  return useCartMutation((id: string) => removeCartItem(id))
}

export function useClearCart() {
  return useCartMutation(() => clearCart())
}
