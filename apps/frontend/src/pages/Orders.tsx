import { useState } from 'react'
import { Link } from 'react-router'
import { useOrders } from '../hooks/useOrders'
import { useAuth } from '../hooks/useAuth'
import { StatusBadge } from '../components/StatusBadge'
import { OrderStatus } from '../enums/OrderStatus'
import { PermissionSlug } from '../enums/PermissionSlug'
import { RouteName, orderPath } from '../enums/RouteName'
import { OrderIncludes } from '../models/Order'
import { errorMessage } from '../lib/api'
import type { OrderFilters } from '../api/orders'

/**
 * Each chip is a named set of query filters rather than a single status, so a
 * view like "today's open orders" is expressed by composing the primitives the
 * API already understands instead of adding a special case to it.
 */
interface OrderPreset {
  key: string
  label: string
  filters: Pick<OrderFilters, 'status' | 'placed_on' | 'exclude_status'>
}

const PRESETS: OrderPreset[] = [
  {
    key: 'active-today',
    label: 'Needs attention',
    filters: {
      placed_on: 'today',
      // Cancelled is excluded alongside completed: this view answers "what is
      // still to do", and a cancelled order is as finished as a delivered one.
      exclude_status: [OrderStatus.COMPLETED, OrderStatus.CANCELLED],
    },
  },
  { key: 'all', label: 'All', filters: {} },
  { key: OrderStatus.PENDING, label: 'Pending', filters: { status: OrderStatus.PENDING } },
  { key: OrderStatus.CONFIRMED, label: 'Confirmed', filters: { status: OrderStatus.CONFIRMED } },
  { key: OrderStatus.PREPARING, label: 'Preparing', filters: { status: OrderStatus.PREPARING } },
  { key: OrderStatus.DELIVERING, label: 'Out for delivery', filters: { status: OrderStatus.DELIVERING } },
  { key: OrderStatus.COMPLETED, label: 'Completed', filters: { status: OrderStatus.COMPLETED } },
  { key: OrderStatus.CANCELLED, label: 'Cancelled', filters: { status: OrderStatus.CANCELLED } },
]

/**
 * One orders page for everybody.
 *
 * The API already decides what you can see: it scopes the list to your own
 * orders unless you hold manage-orders, in which case it returns every order.
 * So this page does not need an administrator twin - it shows the customer
 * column and the status filters to someone who can manage orders, and the same
 * table without them to everyone else.
 */
export function Orders() {
  const { can } = useAuth()
  const manages = can(PermissionSlug.MANAGE_ORDERS)

  // Administrators land on today's open orders, because that is the reason to
  // open this page during service. Customers have no presets and see everything.
  const [presetKey, setPresetKey] = useState<string>(manages ? 'active-today' : 'all')
  const preset = PRESETS.find((option) => option.key === presetKey) ?? PRESETS[1]

  const { data, isPending, isError, error } = useOrders({
    ...preset.filters,
    with: manages ? [OrderIncludes.CUSTOMER] : undefined,
    sort: '!placed_at',
    limit: 50,
  })

  if (isPending) {
    return <div className="skeleton" style={{ height: 220 }} />
  }

  if (isError) {
    return <div className="alert alert--error">{errorMessage(error)}</div>
  }

  const isEmpty = !data || data.orders.length === 0

  if (isEmpty && presetKey === 'all') {
    return (
      <div className="state">
        <h2>{manages ? 'No orders yet' : 'No orders yet'}</h2>
        <p>
          {manages
            ? 'Orders will appear here as customers place them.'
            : 'When you place an order it will show up here.'}
        </p>

        {manages ? null : (
          <Link to={RouteName.MENU} className="btn btn--primary">
            Browse the menu
          </Link>
        )}
      </div>
    )
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>{manages ? 'Orders' : 'Your orders'}</h1>
          <p className="muted small">{data?.total ?? 0} in total</p>
        </div>
      </div>

      {manages ? (
        <div className="filters">
          {PRESETS.map((option) => (
            <button
              key={option.key}
              type="button"
              className={presetKey === option.key ? 'chip chip--active' : 'chip'}
              onClick={() => setPresetKey(option.key)}
            >
              {option.label}
            </button>
          ))}
        </div>
      ) : null}

      {isEmpty ? (
        <div className="state">
          <h2>{presetKey === 'active-today' ? 'Nothing needs attention' : 'No orders here'}</h2>
          <p>
            {presetKey === 'active-today'
              ? 'Every order placed today has been dealt with.'
              : 'Nothing matches this filter.'}
          </p>
        </div>
      ) : (
        <div className="card table-wrap">
          <table>
            <thead>
              <tr>
                <th>Order</th>
                {manages ? <th>Customer</th> : null}
                <th>Placed</th>
                <th>Status</th>
                <th className="num">Items</th>
                <th className="num">Total</th>
                <th />
              </tr>
            </thead>

            <tbody>
              {data?.orders.map((order) => (
                <tr key={order.id}>
                  <td>
                    <Link to={orderPath(order.id)} style={{ fontWeight: 600 }}>
                      {order.order_number}
                    </Link>
                  </td>

                  {manages ? (
                    <td>
                      <div>{order.customer?.name ?? '-'}</div>
                      <div className="small muted">{order.delivery_address}</div>
                    </td>
                  ) : null}

                  <td className="muted">{order.placedAtLabel}</td>

                  <td>
                    <StatusBadge status={order.status} label={order.status_label} />
                    {order.step ? (
                      <span className="step-count">
                        Step {order.step} of {order.total_steps}
                      </span>
                    ) : null}
                  </td>

                  <td className="num">{order.items_count ?? '-'}</td>
                  <td className="num">{order.formattedTotal}</td>
                  <td className="num">
                    <Link to={orderPath(order.id)} className="btn btn--sm">
                      {manages ? 'Manage' : 'View'}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  )
}
