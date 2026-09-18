import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  cancelOrder,
  getOrder,
  listOrders,
  placeOrder,
  updateOrderStatus,
  type CheckoutPayload,
  type OrderFilters,
} from '../api/orders'
import type { OrderStatus } from '../enums/OrderStatus'
import { OrderIncludes } from '../models/Order'
import { queryKeys } from './queryKeys'

export function useOrders(filters: OrderFilters = {}) {
  return useQuery({
    queryKey: queryKeys.orders(filters),
    queryFn: () => listOrders(filters),
  })
}

export function useOrder(id: string | undefined) {
  return useQuery({
    queryKey: queryKeys.order(id ?? ''),
    queryFn: () => getOrder(id as string, [OrderIncludes.CUSTOMER]),
    enabled: Boolean(id),
  })
}

export function usePlaceOrder() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: CheckoutPayload) => placeOrder(payload),
    onSuccess: () => {
      // Checkout empties the cart on the server, so the cached one is stale.
      queryClient.invalidateQueries({ queryKey: queryKeys.cart() })
      queryClient.invalidateQueries({ queryKey: ['orders'] })
    },
  })
}

export function useUpdateOrderStatus() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, status }: { id: string; status: OrderStatus }) =>
      updateOrderStatus(id, status),
    onSuccess: (order) => {
      queryClient.setQueryData(queryKeys.order(order.id), order)
      queryClient.invalidateQueries({ queryKey: ['orders'] })
    },
  })
}

export function useCancelOrder() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: string) => cancelOrder(id),
    onSuccess: (order) => {
      queryClient.setQueryData(queryKeys.order(order.id), order)
      queryClient.invalidateQueries({ queryKey: ['orders'] })
    },
  })
}
