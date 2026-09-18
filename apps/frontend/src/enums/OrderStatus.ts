/**
 * Mirrors app/Enums/OrderStatus.php on the API.
 *
 * The API sends `status`, `status_label` and `allowed_transitions` with every
 * order, so the interface renders the labels and the available next steps that
 * the server gave it rather than reimplementing the lifecycle rules here. These
 * values exist for comparisons and for styling a badge.
 */
export const OrderStatus = {
  PENDING: 'pending',
  CONFIRMED: 'confirmed',
  PREPARING: 'preparing',
  DELIVERING: 'delivering',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
} as const

export type OrderStatus = (typeof OrderStatus)[keyof typeof OrderStatus]

export const ORDER_STATUS_TONE: Record<OrderStatus, 'neutral' | 'info' | 'warn' | 'good' | 'bad'> = {
  [OrderStatus.PENDING]: 'neutral',
  [OrderStatus.CONFIRMED]: 'info',
  [OrderStatus.PREPARING]: 'warn',
  [OrderStatus.DELIVERING]: 'info',
  [OrderStatus.COMPLETED]: 'good',
  [OrderStatus.CANCELLED]: 'bad',
}
