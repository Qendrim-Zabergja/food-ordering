import { useState, type FormEvent } from 'react'
import { Link, useLocation, useNavigate } from 'react-router'
import { useAuth } from '../hooks/useAuth'
import { Field } from '../components/Field'
import { RouteName } from '../enums/RouteName'
import { errorMessage, validationErrors } from '../lib/api'

export function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setErrors({})
    setMessage('')
    setSubmitting(true)

    try {
      await login({ email, password })

      // Back to wherever the guard interrupted them, or the menu.
      const from = (location.state as { from?: string } | null)?.from
      navigate(from ?? RouteName.MENU, { replace: true })
    } catch (error) {
      setErrors(validationErrors(error))
      setMessage(errorMessage(error, 'Could not sign you in.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="auth card">
      <h1>Sign in</h1>
      <p className="muted small">Welcome back. Order something good.</p>

      {message ? <div className="alert alert--error">{message}</div> : null}

      <form onSubmit={handleSubmit} noValidate>
        <Field
          label="Email"
          name="email"
          type="email"
          autoComplete="email"
          value={email}
          errors={errors.email}
          onChange={(event) => setEmail(event.target.value)}
        />

        <Field
          label="Password"
          name="password"
          type="password"
          autoComplete="current-password"
          value={password}
          errors={errors.password}
          onChange={(event) => setPassword(event.target.value)}
        />

        <button type="submit" className="btn btn--primary btn--block" disabled={submitting}>
          {submitting ? 'Signing in…' : 'Sign in'}
        </button>
      </form>

      <p className="small muted" style={{ marginTop: '1rem' }}>
        New here? <Link to={RouteName.REGISTER} style={{ color: 'var(--accent)' }}>Create an account</Link>
      </p>
    </div>
  )
}
