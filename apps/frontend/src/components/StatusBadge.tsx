import { ORDER_STATUS_TONE, type OrderStatus } from '../enums/OrderStatus'

/**
 * The label comes from the API (`status_label`), not from a map here, so the
 * wording stays in one place. This component only picks the colour.
 */
export function StatusBadge({ status, label }: { status: OrderStatus; label: string }) {
  return <span className={`badge badge--${ORDER_STATUS_TONE[status] ?? 'neutral'}`}>{label}</span>
}
