import { Link, useParams } from 'react-router'
import { useCancelOrder, useOrder } from '../hooks/useOrders'
import { StatusBadge } from '../components/StatusBadge'
import { RouteName } from '../enums/RouteName'
import { errorMessage } from '../lib/api'

export function OrderDetail() {
  const { id } = useParams<{ id: string }>()
  const { data: order, isPending, isError, error } = useOrder(id)
  const cancelOrder = useCancelOrder()

  if (isPending) {
    return <div className="skeleton" style={{ height: 320 }} />
  }

  if (isError || !order) {
    return (
      <div className="state">
        <h2>Order not available</h2>
        <p>{errorMessage(error, 'This order could not be loaded.')}</p>
        <Link to={RouteName.ORDERS} className="btn">
          Back to orders
        </Link>
      </div>
    )
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>{order.order_number}</h1>
          <p className="muted small">Placed {order.placedAtLabel}</p>
        </div>

        <div className="row">
          <StatusBadge status={order.status} label={order.status_label} />

          {/*
            The API decides whether a customer may still cancel and sends it as
            can_be_cancelled_by_customer, so this button appears exactly when the
            server would allow the action.
          */}
          {order.can_be_cancelled_by_customer ? (
            <button
              type="button"
              className="btn btn--danger btn--sm"
              disabled={cancelOrder.isPending}
              onClick={() => cancelOrder.mutate(order.id)}
            >
              {cancelOrder.isPending ? 'Cancelling...' : 'Cancel order'}
            </button>
          ) : null}
        </div>
      </div>

      {cancelOrder.isError ? (
        <div className="alert alert--error">{errorMessage(cancelOrder.error)}</div>
      ) : null}

      <div className="split">
        <div className="card">
          <h2>Items</h2>

          {order.items.map((item) => (
            <div className="line" key={item.id}>
              <div className="line__body">
                <div className="line__name">
                  {item.quantity} × {item.product_name}
                </div>
                <div className="small muted">{item.formattedUnitPrice} each</div>
              </div>
              <span className="line__total">{item.formattedLineTotal}</span>
            </div>
          ))}

          <div className="totals">
            <span>Total</span>
            <span>{order.formattedTotal}</span>
          </div>

          <p className="small muted">
            Prices are the ones agreed when the order was placed.
          </p>
        </div>

        <div className="card stack">
          <div>
            <h2>Delivery</h2>
            <p style={{ margin: 0 }}>{order.delivery_address}</p>
            <p className="muted small" style={{ margin: 0 }}>
              {order.phone}
            </p>
          </div>

          {order.notes ? (
            <div>
              <h2>Notes</h2>
              <p className="small" style={{ margin: 0 }}>
                {order.notes}
              </p>
            </div>
          ) : null}

          <Link to={RouteName.ORDERS} className="btn">
            Back to orders
          </Link>
        </div>
      </div>
    </>
  )
}
