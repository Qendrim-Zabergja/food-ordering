import { Model, type ApiPayload } from './Model'

export const ProductCategoryIncludes = {
  PRODUCTS: 'products',
} as const

export class ProductCategory extends Model {
  name = ''
  description: string | null = null
  sort_order = 0
  is_active = true
  products_count: number | null = null

  hydrate(data: ApiPayload): this {
    return super.hydrate(data)
  }
}
