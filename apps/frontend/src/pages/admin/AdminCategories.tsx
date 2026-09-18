import { useState } from 'react'
import { useProductCategories } from '../../hooks/useProducts'
import {
  useDeleteProductCategory,
  useSaveProductCategory,
} from '../../hooks/useProductCategories'
import { AdminNav } from '../../components/AdminNav'
import { Field } from '../../components/Field'
import { errorMessage, validationErrors } from '../../lib/api'
import type { ProductCategory } from '../../models/ProductCategory'

interface FormState {
  name: string
  description: string
  sort_order: string
  is_active: boolean
}

const EMPTY: FormState = { name: '', description: '', sort_order: '0', is_active: true }

export function AdminCategories() {
  const [editing, setEditing] = useState<ProductCategory | null>(null)
  const [form, setForm] = useState<FormState>(EMPTY)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')
  const [failed, setFailed] = useState(false)

  const { data: categories, isPending } = useProductCategories()
  const saveCategory = useSaveProductCategory(editing?.id)
  const deleteCategory = useDeleteProductCategory()

  function startCreate() {
    setEditing(null)
    setForm(EMPTY)
    setErrors({})
    setMessage('')
    setFailed(false)
  }

  function startEdit(category: ProductCategory) {
    setEditing(category)
    setForm({
      name: category.name,
      description: category.description ?? '',
      sort_order: String(category.sort_order),
      is_active: category.is_active,
    })
    setErrors({})
    setMessage('')
    setFailed(false)
  }

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()
    setErrors({})
    setMessage('')
    setFailed(false)

    try {
      await saveCategory.mutateAsync({
        name: form.name,
        description: form.description || null,
        sort_order: Number(form.sort_order),
        is_active: form.is_active,
      })

      // Reset inline rather than through startCreate(), which would clear the
      // message we just set.
      setMessage(editing ? 'Category updated.' : 'Category created.')
      setEditing(null)
      setForm(EMPTY)
    } catch (error) {
      setErrors(validationErrors(error))
      setMessage(errorMessage(error, 'Could not save the category.'))
      setFailed(true)
    }
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Categories</h1>
          <p className="muted small">{categories ? `${categories.length} categories` : 'Loading...'}</p>
        </div>

        <button type="button" className="btn" onClick={startCreate}>
          New category
        </button>
      </div>

      <AdminNav />

      {message ? (
        <div className={failed ? 'alert alert--error' : 'alert alert--success'}>{message}</div>
      ) : null}

      {deleteCategory.isError ? (
        <div className="alert alert--error">{errorMessage(deleteCategory.error)}</div>
      ) : null}

      <div className="split">
        <div className="card table-wrap">
          {isPending ? <div className="skeleton" style={{ height: 180 }} /> : null}

          {categories ? (
            <table>
              <thead>
                <tr>
                  <th className="num">#</th>
                  <th>Name</th>
                  <th className="num">Products</th>
                  <th>Active</th>
                  <th />
                </tr>
              </thead>

              <tbody>
                {categories.map((category) => (
                  <tr key={category.id}>
                    <td className="num muted">{category.sort_order}</td>
                    <td style={{ fontWeight: 600 }}>{category.name}</td>
                    <td className="num">{category.products_count ?? '-'}</td>
                    <td>
                      <span className={category.is_active ? 'badge badge--good' : 'badge badge--neutral'}>
                        {category.is_active ? 'Yes' : 'No'}
                      </span>
                    </td>
                    <td className="num">
                      <div className="row" style={{ justifyContent: 'flex-end' }}>
                        <button type="button" className="btn btn--sm" onClick={() => startEdit(category)}>
                          Edit
                        </button>
                        <button
                          type="button"
                          className="btn btn--sm btn--danger"
                          disabled={deleteCategory.isPending}
                          onClick={() => deleteCategory.mutate(category.id)}
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : null}
        </div>

        <form className="card" onSubmit={handleSubmit} noValidate>
          <h2>{editing ? `Edit ${editing.name}` : 'New category'}</h2>

          <Field
            label="Name"
            name="name"
            value={form.name}
            errors={errors.name}
            onChange={(event) => setForm({ ...form, name: event.target.value })}
          />

          <Field
            label="Sort order"
            name="sort_order"
            type="number"
            min="0"
            value={form.sort_order}
            errors={errors.sort_order}
            onChange={(event) => setForm({ ...form, sort_order: event.target.value })}
          />

          <div className="field">
            <label className="field__label" htmlFor="category-description">
              Description
            </label>
            <textarea
              id="category-description"
              rows={3}
              value={form.description}
              onChange={(event) => setForm({ ...form, description: event.target.value })}
            />
          </div>

          <label className="row small" style={{ marginBottom: '0.9rem' }}>
            <input
              type="checkbox"
              style={{ width: 'auto' }}
              checked={form.is_active}
              onChange={(event) => setForm({ ...form, is_active: event.target.checked })}
            />
            Active
          </label>

          <button type="submit" className="btn btn--primary btn--block" disabled={saveCategory.isPending}>
            {saveCategory.isPending ? 'Saving...' : editing ? 'Save changes' : 'Create category'}
          </button>

          {editing ? (
            <button
              type="button"
              className="btn btn--block"
              style={{ marginTop: '0.5rem' }}
              onClick={startCreate}
            >
              Cancel
            </button>
          ) : null}
        </form>
      </div>
    </>
  )
}
