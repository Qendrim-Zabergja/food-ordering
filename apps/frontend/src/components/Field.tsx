import type { InputHTMLAttributes } from 'react'

interface FieldProps extends InputHTMLAttributes<HTMLInputElement> {
  label: string
  /** Messages for this field, straight from the API's 422 response. */
  errors?: string[]
}

export function Field({ label, errors = [], id, ...props }: FieldProps) {
  const fieldId = id ?? props.name
  const errorId = `${fieldId}-error`
  const hasError = errors.length > 0

  return (
    <div className="field">
      <label className="field__label" htmlFor={fieldId}>
        {label}
      </label>

      <input
        id={fieldId}
        aria-invalid={hasError || undefined}
        aria-describedby={hasError ? errorId : undefined}
        {...props}
      />

      {hasError ? (
        <span className="field__error" id={errorId}>
          {errors[0]}
        </span>
      ) : null}
    </div>
  )
}
