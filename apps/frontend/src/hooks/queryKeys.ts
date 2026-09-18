import type { OrderFilters } from '../api/orders'
import type { ProductFilters } from '../api/products'

/**
 * Every cache key in one place, so invalidating after a mutation cannot miss a
 * query by spelling its key differently.
 */
export const queryKeys = {
  products: (filters: ProductFilters = {}) => ['products', filters] as const,
  product: (id: string) => ['products', id] as const,
  productCategories: () => ['product-categories'] as const,
  cart: () => ['cart'] as const,
  orders: (filters: OrderFilters = {}) => ['orders', filters] as const,
  order: (id: string) => ['orders', id] as const,
}
