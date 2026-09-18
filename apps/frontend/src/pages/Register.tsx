import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router'
import { useAuth } from '../hooks/useAuth'
import { Field } from '../components/Field'
import { RouteName } from '../enums/RouteName'
import { errorMessage, validationErrors } from '../lib/api'

export function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()

  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')
  const [submitting, setSubmitting] = useState(false)

  function update(field: keyof typeof form) {
    return (event: { target: { value: string } }) =>
      setForm((current) => ({ ...current, [field]: event.target.value }))
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setErrors({})
    setMessage('')
    setSubmitting(true)

    try {
      await register(form)
      navigate(RouteName.MENU, { replace: true })
    } catch (error) {
      setErrors(validationErrors(error))
      setMessage(errorMessage(error, 'Could not create your account.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="auth card">
      <h1>Create account</h1>
      <p className="muted small">It takes a moment, then you can order.</p>

      {message ? <div className="alert alert--error">{message}</div> : null}

      <form onSubmit={handleSubmit} noValidate>
        <Field
          label="Name"
          name="name"
          autoComplete="name"
          value={form.name}
          errors={errors.name}
          onChange={update('name')}
        />

        <Field
          label="Email"
          name="email"
          type="email"
          autoComplete="email"
          value={form.email}
          errors={errors.email}
          onChange={update('email')}
        />

        <Field
          label="Password"
          name="password"
          type="password"
          autoComplete="new-password"
          value={form.password}
          errors={errors.password}
          onChange={update('password')}
        />

        <Field
          label="Confirm password"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          value={form.password_confirmation}
          errors={errors.password_confirmation}
          onChange={update('password_confirmation')}
        />

        <button type="submit" className="btn btn--primary btn--block" disabled={submitting}>
          {submitting ? 'Creating…' : 'Create account'}
        </button>
      </form>

      <p className="small muted" style={{ marginTop: '1rem' }}>
        Already have an account? <Link to={RouteName.LOGIN} style={{ color: 'var(--accent)' }}>Sign in</Link>
      </p>
    </div>
  )
}
