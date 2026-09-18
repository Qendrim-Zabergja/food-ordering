import { api, type Paginated } from '../lib/api'
import { ProductCategory } from '../models/ProductCategory'
import type { ApiPayload } from '../models/Model'

export interface ProductCategoryPayload {
  name: string
  description?: string | null
  sort_order?: number
  is_active?: boolean
}

export async function listProductCategories(
  options: { search?: string; sort?: string; limit?: number } = {},
): Promise<ProductCategory[]> {
  const { search, sort, limit } = options
  const { data } = await api.get<Paginated<ApiPayload>>('/product-categories', {
    params: { filter: { search }, sort: sort ?? 'sort_order', limit: limit ?? 100 },
  })

  return ProductCategory.collection(data.data)
}

export async function createProductCategory(
  payload: ProductCategoryPayload,
): Promise<ProductCategory> {
  const { data } = await api.post<ApiPayload>('/product-categories', payload)

  return new ProductCategory().hydrate(data)
}

export async function updateProductCategory(
  id: string,
  payload: Partial<ProductCategoryPayload>,
): Promise<ProductCategory> {
  const { data } = await api.patch<ApiPayload>(`/product-categories/${id}`, payload)

  return new ProductCategory().hydrate(data)
}

export async function deleteProductCategory(id: string): Promise<void> {
  await api.delete(`/product-categories/${id}`)
}
