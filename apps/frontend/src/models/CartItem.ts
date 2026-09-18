import { Model, formatPrice, type ApiPayload } from './Model'
import { Product } from './Product'

export class CartItem extends Model {
  quantity = 1
  unit_price_cents = 0
  unit_price = 0
  line_total_cents = 0
  line_total = 0
  product: Product | null = null

  hydrate(data: ApiPayload): this {
    super.hydrate(data)

    this.product = data.product ? new Product().hydrate(data.product as ApiPayload) : null

    return this
  }

  get formattedLineTotal(): string {
    return formatPrice(this.line_total_cents)
  }

  get formattedUnitPrice(): string {
    return formatPrice(this.unit_price_cents)
  }
}
