import { useState } from 'react'
import { Link } from 'react-router'
import { useOrders, useUpdateOrderStatus } from '../../hooks/useOrders'
import { AdminNav } from '../../components/AdminNav'
import { StatusBadge } from '../../components/StatusBadge'
import { OrderStatus } from '../../enums/OrderStatus'
import { OrderIncludes } from '../../models/Order'
import { orderPath } from '../../enums/RouteName'
import { errorMessage } from '../../lib/api'

const FILTERS = [
  { label: 'All', value: '' },
  { label: 'Pending', value: OrderStatus.PENDING },
  { label: 'Confirmed', value: OrderStatus.CONFIRMED },
  { label: 'Preparing', value: OrderStatus.PREPARING },
  { label: 'Out for delivery', value: OrderStatus.DELIVERING },
  { label: 'Completed', value: OrderStatus.COMPLETED },
  { label: 'Cancelled', value: OrderStatus.CANCELLED },
]

export function AdminOrders() {
  const [status, setStatus] = useState<string>('')

  const { data, isPending, isError, error } = useOrders({
    status: status ? (status as OrderStatus) : undefined,
    with: [OrderIncludes.CUSTOMER],
    sort: '!placed_at',
    limit: 50,
  })

  const updateStatus = useUpdateOrderStatus()

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Orders</h1>
          <p className="muted small">{data ? `${data.total} order(s)` : 'Loading...'}</p>
        </div>
      </div>

      <AdminNav />

      <div className="filters">
        {FILTERS.map((filter) => (
          <button
            key={filter.value}
            type="button"
            className={status === filter.value ? 'chip chip--active' : 'chip'}
            onClick={() => setStatus(filter.value)}
          >
            {filter.label}
          </button>
        ))}
      </div>

      {isError ? <div className="alert alert--error">{errorMessage(error)}</div> : null}

      {updateStatus.isError ? (
        <div className="alert alert--error">{errorMessage(updateStatus.error)}</div>
      ) : null}

      {isPending ? <div className="skeleton" style={{ height: 220 }} /> : null}

      {data && data.orders.length === 0 ? (
        <div className="state">
          <h2>No orders here</h2>
          <p>Nothing matches this filter.</p>
        </div>
      ) : null}

      {data && data.orders.length > 0 ? (
        <div className="card table-wrap">
          <table>
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Placed</th>
                <th>Status</th>
                <th className="num">Total</th>
                <th>Move to</th>
              </tr>
            </thead>

            <tbody>
              {data.orders.map((order) => (
                <tr key={order.id}>
                  <td>
                    <Link to={orderPath(order.id)} style={{ fontWeight: 600 }}>
                      {order.order_number}
                    </Link>
                  </td>
                  <td>
                    <div>{order.customer?.name ?? '-'}</div>
                    <div className="small muted">{order.delivery_address}</div>
                  </td>
                  <td className="muted small">{order.placedAtLabel}</td>
                  <td>
                    <StatusBadge status={order.status} label={order.status_label} />
                  </td>
                  <td className="num">{order.formattedTotal}</td>
                  <td>
                    {/*
                      The buttons come from allowed_transitions, which the API
                      derives from the OrderStatus enum. The lifecycle rules are
                      never restated here, so they cannot drift out of step.
                    */}
                    {order.is_final ? (
                      <span className="small muted">Final</span>
                    ) : (
                      <div className="row">
                        {order.allowed_transitions.map((next) => (
                          <button
                            key={next}
                            type="button"
                            className="btn btn--sm"
                            disabled={updateStatus.isPending}
                            onClick={() => updateStatus.mutate({ id: order.id, status: next })}
                          >
                            {next}
                          </button>
                        ))}
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : null}
    </>
  )
}
