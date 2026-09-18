import type { Order } from '../models/Order'

/**
 * The full order lifecycle with the current stage marked.
 *
 * Every stage, its label and whether it has been reached come from the API's
 * `progress` array. Nothing about the sequence is decided here, which is why
 * adding or removing a status on the server needs no change in this file.
 */
export function OrderProgress({ order }: { order: Order }) {
  if (order.status === 'cancelled') {
    return (
      <div className="progress progress--cancelled">
        <span className="badge badge--bad">Cancelled</span>
        <span className="small muted">This order did not continue through the usual stages.</span>
      </div>
    )
  }

  if (order.progress.length === 0) {
    return null
  }

  return (
    <ol className="progress" aria-label={`Order progress: step ${order.step} of ${order.total_steps}`}>
      {order.progress.map((stage) => (
        <li
          key={stage.status}
          className={
            stage.current
              ? 'progress__step progress__step--current'
              : stage.reached
                ? 'progress__step progress__step--reached'
                : 'progress__step'
          }
          aria-current={stage.current ? 'step' : undefined}
        >
          <span className="progress__dot" aria-hidden="true">
            {stage.reached && !stage.current ? '✓' : ''}
          </span>
          <span className="progress__label">{stage.label}</span>
        </li>
      ))}
    </ol>
  )
}
