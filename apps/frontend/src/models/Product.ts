import { Model, formatPrice, type ApiPayload } from './Model'
import { ProductCategory } from './ProductCategory'

export const ProductIncludes = {
  CATEGORY: 'category',
} as const

export class Product extends Model {
  name = ''
  description: string | null = null
  price = 0
  price_cents = 0
  image_url: string | null = null
  is_available = true
  category: ProductCategory | null = null

  hydrate(data: ApiPayload): this {
    super.hydrate(data)

    // Nested payloads become models too, so a caller never has to know whether
    // it is holding a Product or a plain object.
    this.category = data.category
      ? new ProductCategory().hydrate(data.category as ApiPayload)
      : null

    return this
  }

  get formattedPrice(): string {
    return formatPrice(this.price_cents)
  }
}
