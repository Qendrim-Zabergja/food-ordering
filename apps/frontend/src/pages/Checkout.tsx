import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router'
import { useCart } from '../hooks/useCart'
import { usePlaceOrder } from '../hooks/useOrders'
import { Field } from '../components/Field'
import { RouteName, orderPath } from '../enums/RouteName'
import { errorMessage, validationErrors } from '../lib/api'

export function Checkout() {
  const { data: cart, isPending } = useCart()
  const placeOrder = usePlaceOrder()
  const navigate = useNavigate()

  const [form, setForm] = useState({ delivery_address: '', phone: '', notes: '' })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')

  function update(field: keyof typeof form) {
    return (event: { target: { value: string } }) =>
      setForm((current) => ({ ...current, [field]: event.target.value }))
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setErrors({})
    setMessage('')

    try {
      const order = await placeOrder.mutateAsync({
        delivery_address: form.delivery_address,
        phone: form.phone,
        notes: form.notes || null,
      })

      navigate(orderPath(order.id), { replace: true })
    } catch (error) {
      setErrors(validationErrors(error))
      // The API refuses checkout when the cart is empty, or when something sold
      // out while it sat there. Both arrive under the `cart` key.
      setMessage(errorMessage(error, 'Could not place your order.'))
    }
  }

  if (isPending) {
    return <div className="skeleton" style={{ height: 240 }} />
  }

  if (!cart || cart.isEmpty) {
    return (
      <div className="state">
        <h2>Nothing to check out</h2>
        <p>Your cart is empty.</p>
        <Link to={RouteName.MENU} className="btn btn--primary">
          Browse the menu
        </Link>
      </div>
    )
  }

  return (
    <>
      <div className="page-head">
        <h1>Checkout</h1>
      </div>

      {message ? <div className="alert alert--error">{message}</div> : null}

      <div className="split">
        <form className="card" onSubmit={handleSubmit} noValidate>
          <h2>Delivery details</h2>

          <Field
            label="Delivery address"
            name="delivery_address"
            autoComplete="street-address"
            value={form.delivery_address}
            errors={errors.delivery_address}
            onChange={update('delivery_address')}
          />

          <Field
            label="Phone"
            name="phone"
            type="tel"
            autoComplete="tel"
            value={form.phone}
            errors={errors.phone}
            onChange={update('phone')}
          />

          <div className="field">
            <label className="field__label" htmlFor="notes">
              Notes (optional)
            </label>
            <textarea
              id="notes"
              name="notes"
              rows={3}
              value={form.notes}
              onChange={update('notes')}
            />
            {errors.notes ? <span className="field__error">{errors.notes[0]}</span> : null}
          </div>

          <button
            type="submit"
            className="btn btn--primary btn--block"
            disabled={placeOrder.isPending}
          >
            {placeOrder.isPending ? 'Placing order...' : `Place order · ${cart.formattedSubtotal}`}
          </button>
        </form>

        <div className="card">
          <h2>Your order</h2>

          {cart.items.map((item) => (
            <div className="line" key={item.id}>
              <div className="line__body">
                <div className="line__name">
                  {item.quantity} × {item.product?.name ?? 'Unknown item'}
                </div>
                <div className="small muted">{item.formattedUnitPrice} each</div>
              </div>
              <span className="line__total">{item.formattedLineTotal}</span>
            </div>
          ))}

          <div className="totals">
            <span>Total</span>
            <span>{cart.formattedSubtotal}</span>
          </div>
        </div>
      </div>
    </>
  )
}
