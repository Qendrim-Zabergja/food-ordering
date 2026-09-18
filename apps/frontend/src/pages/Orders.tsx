import { Link } from 'react-router'
import { useOrders } from '../hooks/useOrders'
import { StatusBadge } from '../components/StatusBadge'
import { RouteName, orderPath } from '../enums/RouteName'
import { errorMessage } from '../lib/api'

export function Orders() {
  const { data, isPending, isError, error } = useOrders({ sort: '!placed_at', limit: 25 })

  if (isPending) {
    return <div className="skeleton" style={{ height: 220 }} />
  }

  if (isError) {
    return <div className="alert alert--error">{errorMessage(error)}</div>
  }

  if (!data || data.orders.length === 0) {
    return (
      <div className="state">
        <h2>No orders yet</h2>
        <p>When you place an order it will show up here.</p>
        <Link to={RouteName.MENU} className="btn btn--primary">
          Browse the menu
        </Link>
      </div>
    )
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Your orders</h1>
          <p className="muted small">{data.total} in total</p>
        </div>
      </div>

      <div className="card table-wrap">
        <table>
          <thead>
            <tr>
              <th>Order</th>
              <th>Placed</th>
              <th>Status</th>
              <th className="num">Items</th>
              <th className="num">Total</th>
              <th />
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
                <td className="muted">{order.placedAtLabel}</td>
                <td>
                  <StatusBadge status={order.status} label={order.status_label} />
                </td>
                <td className="num">{order.items_count ?? '-'}</td>
                <td className="num">{order.formattedTotal}</td>
                <td className="num">
                  <Link to={orderPath(order.id)} className="btn btn--sm">
                    View
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  )
}
