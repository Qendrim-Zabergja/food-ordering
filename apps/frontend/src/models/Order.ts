import { Model, formatPrice, type ApiPayload } from './Model'
import { OrderItem } from './OrderItem'
import { User } from './User'
import { OrderStatus } from '../enums/OrderStatus'

export const OrderIncludes = {
  CUSTOMER: 'user',
} as const

/** One stage of the lifecycle, as the API describes it. */
export interface OrderProgressStage {
  status: OrderStatus
  label: string
  reached: boolean
  current: boolean
}

export class Order extends Model {
  order_number = ''
  status: OrderStatus = OrderStatus.PENDING
  status_label = ''
  is_final = false

  /**
   * Which statuses this order may move to, decided by the API. The admin panel
   * renders exactly these, so the lifecycle rules live in one place - on the
   * server - instead of being duplicated here and drifting.
   */
  allowed_transitions: OrderStatus[] = []
  can_be_cancelled_by_customer = false

  /**
   * The full lifecycle with the current position marked. The stages and their
   * order come from the API, so this client never restates them.
   */
  progress: OrderProgressStage[] = []
  step: number | null = null
  total_steps = 0

  total_cents = 0
  total = 0
  delivery_address = ''
  phone = ''
  notes: string | null = null
  placed_at: string | null = null
  items_count: number | null = null

  items: OrderItem[] = []
  customer: User | null = null

  hydrate(data: ApiPayload): this {
    super.hydrate(data)

    this.items = OrderItem.collection(data.items as ApiPayload[] | undefined)
    this.customer = data.customer ? new User().hydrate(data.customer as ApiPayload) : null
    this.allowed_transitions = (data.allowed_transitions as OrderStatus[]) ?? []
    this.progress = (data.progress as OrderProgressStage[]) ?? []

    return this
  }

  get formattedTotal(): string {
    return formatPrice(this.total_cents)
  }

  get placedAtLabel(): string {
    if (!this.placed_at) {
      return ''
    }

    return new Intl.DateTimeFormat('en-GB', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(new Date(this.placed_at))
  }
}
