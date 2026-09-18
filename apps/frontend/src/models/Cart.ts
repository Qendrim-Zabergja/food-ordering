import { CartItem } from './CartItem'
import { Model, formatPrice, type ApiPayload } from './Model'

export class Cart extends Model {
  items: CartItem[] = []
  items_count = 0
  subtotal_cents = 0
  subtotal = 0

  hydrate(data: ApiPayload): this {
    super.hydrate(data)

    this.items = CartItem.collection(data.items as ApiPayload[] | undefined)

    return this
  }

  get isEmpty(): boolean {
    return this.items.length === 0
  }

  get formattedSubtotal(): string {
    return formatPrice(this.subtotal_cents)
  }
}
