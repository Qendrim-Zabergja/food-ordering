import { Link, useParams } from 'react-router'
import { useCancelOrder, useOrder, useUpdateOrderStatus } from '../hooks/useOrders'
import { useAuth } from '../hooks/useAuth'
import { OrderProgress } from '../components/OrderProgress'
import { StatusBadge } from '../components/StatusBadge'
import { OrderStatus } from '../enums/OrderStatus'
import { PermissionSlug } from '../enums/PermissionSlug'
import { RouteName } from '../enums/RouteName'
import { errorMessage } from '../lib/api'

export function OrderDetail() {
  const { id } = useParams<{ id: string }>()
  const { can } = useAuth()
  const manages = can(PermissionSlug.MANAGE_ORDERS)

  const { data: order, isPending, isError, error } = useOrder(id)
  const updateStatus = useUpdateOrderStatus()
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

  // Cancelling has its own endpoint because who may do it differs, so it is
  // kept out of the forward-step buttons here too.
  const forwardSteps = order.allowed_transitions.filter(
    (status) => status !== OrderStatus.CANCELLED,
  )

  const mutationError = updateStatus.error ?? cancelOrder.error
  const busy = updateStatus.isPending || cancelOrder.isPending

  return (
    <>
      <div className="page-head">
        <div>
          <h1>{order.order_number}</h1>
          <p className="muted small">
            Placed {order.placedAtLabel}
            {manages && order.customer ? ` · ${order.customer.name}` : ''}
          </p>
        </div>

        <div className="row">
          <StatusBadge status={order.status} label={order.status_label} />

          {/*
            The API decides whether a customer may still cancel and sends it as
            can_be_cancelled_by_customer, so this button appears exactly when the
            server would allow the action.
          */}
          {!manages && order.can_be_cancelled_by_customer ? (
            <button
              type="button"
              className="btn btn--danger btn--sm"
              disabled={busy}
              onClick={() => cancelOrder.mutate(order.id)}
            >
              {cancelOrder.isPending ? 'Cancelling...' : 'Cancel order'}
            </button>
          ) : null}
        </div>
      </div>

      {mutationError ? <div className="alert alert--error">{errorMessage(mutationError)}</div> : null}

      <OrderProgress order={order} />

      {/*
        Managing the order happens here rather than on a separate admin screen:
        this is where the items, the customer and the delivery details already
        are, which is what you want in front of you when moving an order on.
      */}
      {manages ? (
        <div className="card stack" style={{ marginBottom: '1.25rem' }}>
          <div className="row" style={{ justifyContent: 'space-between' }}>
            <h2 style={{ margin: 0 }}>Manage this order</h2>
            {order.is_final ? <span className="small muted">No further changes possible</span> : null}
          </div>

          {order.is_final ? null : (
            <div className="row">
              {forwardSteps.map((next) => (
                <button
                  key={next}
                  type="button"
                  className="btn btn--primary btn--sm"
                  disabled={busy}
                  onClick={() => updateStatus.mutate({ id: order.id, status: next })}
                >
                  Move to {next}
                </button>
              ))}

              {order.allowed_transitions.includes(OrderStatus.CANCELLED) ? (
                <button
                  type="button"
                  className="btn btn--danger btn--sm"
                  disabled={busy}
                  onClick={() => cancelOrder.mutate(order.id)}
                >
                  Cancel order
                </button>
              ) : null}
            </div>
          )}
        </div>
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

          <p className="small muted">Prices are the ones agreed when the order was placed.</p>
        </div>

        <div className="card stack">
          <div>
            <h2>Delivery</h2>
            <p style={{ margin: 0 }}>{order.delivery_address}</p>
            <p className="muted small" style={{ margin: 0 }}>
              {order.phone}
            </p>
          </div>

          {manages && order.customer ? (
            <div>
              <h2>Customer</h2>
              <p style={{ margin: 0 }}>{order.customer.name}</p>
              <p className="muted small" style={{ margin: 0 }}>
                {order.customer.email}
              </p>
            </div>
          ) : null}

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
