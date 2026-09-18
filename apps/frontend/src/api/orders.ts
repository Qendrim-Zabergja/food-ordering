import { api, type Paginated } from '../lib/api'
import { Order } from '../models/Order'
import type { OrderStatus } from '../enums/OrderStatus'
import type { ApiPayload } from '../models/Model'

export interface OrderFilters {
  search?: string
  status?: OrderStatus | OrderStatus[]
  /** A date, or the literal 'today' which the API resolves server-side. */
  placed_on?: string
  exclude_status?: OrderStatus[]
  with?: string[]
  sort?: string
  limit?: number
  page?: number
}

export interface CheckoutPayload {
  delivery_address: string
  phone: string
  notes?: string | null
}

export async function listOrders(
  filters: OrderFilters = {},
): Promise<{ orders: Order[]; total: number; lastPage: number }> {
  const { with: includes, sort, limit, page, ...rest } = filters

  const { data } = await api.get<Paginated<ApiPayload>>('/orders', {
    params: { filter: rest, with: includes, sort, limit, page },
  })

  return {
    orders: Order.collection(data.data),
    total: data.meta.total,
    lastPage: data.meta.last_page,
  }
}

export async function getOrder(id: string, includes: string[] = []): Promise<Order> {
  const { data } = await api.get<ApiPayload>(`/orders/${id}`, { params: { with: includes } })

  return new Order().hydrate(data)
}

/** Checkout. The body carries delivery details only - items and prices come from the cart. */
export async function placeOrder(payload: CheckoutPayload): Promise<Order> {
  const { data } = await api.post<ApiPayload>('/orders', payload)

  return new Order().hydrate(data)
}

export async function updateOrderStatus(id: string, status: OrderStatus): Promise<Order> {
  const { data } = await api.patch<ApiPayload>(`/orders/${id}/status`, { status })

  return new Order().hydrate(data)
}

export async function cancelOrder(id: string): Promise<Order> {
  const { data } = await api.patch<ApiPayload>(`/orders/${id}/cancel`)

  return new Order().hydrate(data)
}
