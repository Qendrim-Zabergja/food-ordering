import { Link } from 'react-router'
import { useCart, useClearCart, useRemoveCartItem, useUpdateCartItem } from '../hooks/useCart'
import { RouteName } from '../enums/RouteName'
import { errorMessage } from '../lib/api'
import type { CartItem } from '../models/CartItem'

const MAX_QUANTITY = 99

export function Cart() {
  const { data: cart, isPending, isError, error } = useCart()
  const updateItem = useUpdateCartItem()
  const removeItem = useRemoveCartItem()
  const clearCart = useClearCart()

  const busy = updateItem.isPending || removeItem.isPending || clearCart.isPending
  const mutationError = updateItem.error ?? removeItem.error ?? clearCart.error

  function changeQuantity(item: CartItem, next: number) {
    // Zero is not a quantity the API accepts - removing a line is a delete.
    if (next < 1) {
      removeItem.mutate(item.id)

      return
    }

    updateItem.mutate({ id: item.id, quantity: Math.min(next, MAX_QUANTITY) })
  }

  if (isPending) {
    return <div className="skeleton" style={{ height: 240 }} />
  }

  if (isError) {
    return <div className="alert alert--error">{errorMessage(error)}</div>
  }

  if (!cart || cart.isEmpty) {
    return (
      <div className="state">
        <h2>Your cart is empty</h2>
        <p>Nothing here yet.</p>
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
          <h1>Your cart</h1>
          <p className="muted small">
            {cart.items_count} item{cart.items_count === 1 ? '' : 's'}
          </p>
        </div>

        <button
          type="button"
          className="btn btn--danger btn--sm"
          disabled={busy}
          onClick={() => clearCart.mutate(undefined)}
        >
          Empty cart
        </button>
      </div>

      {mutationError ? <div className="alert alert--error">{errorMessage(mutationError)}</div> : null}

      <div className="split">
        <div className="card">
          {cart.items.map((item) => (
            <div className="line" key={item.id}>
              {item.product?.image_url ? (
                <img className="line__thumb" src={item.product.image_url} alt="" width={48} height={48} />
              ) : null}

              <div className="line__body">
                <div className="line__name">{item.product?.name ?? 'Unknown item'}</div>
                <div className="small muted">{item.formattedUnitPrice} each</div>
              </div>

              <div className="qty">
                <button
                  type="button"
                  aria-label={`Decrease quantity of ${item.product?.name ?? 'item'}`}
                  disabled={busy}
                  onClick={() => changeQuantity(item, item.quantity - 1)}
                >
                  &minus;
                </button>

                <span className="qty__value">{item.quantity}</span>

                <button
                  type="button"
                  aria-label={`Increase quantity of ${item.product?.name ?? 'item'}`}
                  disabled={busy || item.quantity >= MAX_QUANTITY}
                  onClick={() => changeQuantity(item, item.quantity + 1)}
                >
                  +
                </button>
              </div>

              <span className="line__total">{item.formattedLineTotal}</span>

              <button
                type="button"
                className="btn btn--danger btn--sm"
                aria-label={`Remove ${item.product?.name ?? 'item'}`}
                disabled={busy}
                onClick={() => removeItem.mutate(item.id)}
              >
                Remove
              </button>
            </div>
          ))}
        </div>

        <div className="card">
          <h2>Summary</h2>

          <div className="totals">
            <span>Total</span>
            <span>{cart.formattedSubtotal}</span>
          </div>

          <p className="small muted">Prices are the current menu prices.</p>

          <Link to={RouteName.CHECKOUT} className="btn btn--primary btn--block">
            Checkout
          </Link>
        </div>
      </div>
    </>
  )
}
