import { api, type Paginated } from '../lib/api'
import { Product } from '../models/Product'
import type { ApiPayload } from '../models/Model'

export interface ProductFilters {
  search?: string
  category?: string
  is_available?: boolean
  with?: string[]
  sort?: string
  limit?: number
}

export interface ProductPayload {
  category: { id: string }
  name: string
  description?: string | null
  price: number
  image_url?: string | null
  is_available?: boolean
}

function params({ with: includes, sort, limit, ...filters }: ProductFilters) {
  return {
    filter: filters,
    with: includes,
    sort,
    limit,
  }
}

export async function listProducts(
  filters: ProductFilters = {},
): Promise<{ products: Product[]; total: number }> {
  const { data } = await api.get<Paginated<ApiPayload>>('/products', { params: params(filters) })

  return { products: Product.collection(data.data), total: data.meta.total }
}

export async function getProduct(id: string, includes: string[] = []): Promise<Product> {
  const { data } = await api.get<ApiPayload>(`/products/${id}`, { params: { with: includes } })

  return new Product().hydrate(data)
}

export async function createProduct(payload: ProductPayload): Promise<Product> {
  const { data } = await api.post<ApiPayload>('/products', payload)

  return new Product().hydrate(data)
}

export async function updateProduct(id: string, payload: Partial<ProductPayload>): Promise<Product> {
  const { data } = await api.patch<ApiPayload>(`/products/${id}`, payload)

  return new Product().hydrate(data)
}

export async function deleteProduct(id: string): Promise<void> {
  await api.delete(`/products/${id}`)
}
