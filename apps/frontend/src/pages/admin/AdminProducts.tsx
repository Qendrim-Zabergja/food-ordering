import { useState } from 'react'
import {
  useDeleteProduct,
  useProductCategories,
  useProducts,
  useSaveProduct,
} from '../../hooks/useProducts'
import { AdminNav } from '../../components/AdminNav'
import { Field } from '../../components/Field'
import { errorMessage, validationErrors } from '../../lib/api'
import type { Product } from '../../models/Product'

interface FormState {
  categoryId: string
  name: string
  description: string
  price: string
  is_available: boolean
}

const EMPTY: FormState = {
  categoryId: '',
  name: '',
  description: '',
  price: '',
  is_available: true,
}

export function AdminProducts() {
  const [editing, setEditing] = useState<Product | null>(null)
  const [form, setForm] = useState<FormState>(EMPTY)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')
  const [failed, setFailed] = useState(false)

  const { data: categories } = useProductCategories()
  const { data, isPending } = useProducts({ with: ['category'], sort: 'name', limit: 100 })

  const saveProduct = useSaveProduct(editing?.id)
  const deleteProduct = useDeleteProduct()

  function startCreate() {
    setEditing(null)
    setForm({ ...EMPTY, categoryId: categories?.[0]?.id ?? '' })
    setErrors({})
    setMessage('')
    setFailed(false)
  }

  function startEdit(product: Product) {
    setEditing(product)
    setForm({
      categoryId: product.category?.id ?? '',
      name: product.name,
      description: product.description ?? '',
      // The form works in major units; the API is told a price and stores cents.
      price: String(product.price),
      is_available: product.is_available,
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
      await saveProduct.mutateAsync({
        category: { id: form.categoryId },
        name: form.name,
        description: form.description || null,
        price: Number(form.price),
        is_available: form.is_available,
      })

      setMessage(editing ? 'Product updated.' : 'Product created.')
      setEditing(null)
      setForm({ ...EMPTY, categoryId: categories?.[0]?.id ?? '' })
    } catch (error) {
      setErrors(validationErrors(error))
      setMessage(errorMessage(error, 'Could not save the product.'))
      setFailed(true)
    }
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Products</h1>
          <p className="muted small">{data ? `${data.total} on the menu` : 'Loading...'}</p>
        </div>

        <button type="button" className="btn" onClick={startCreate}>
          New product
        </button>
      </div>

      <AdminNav />

      {message ? (
        <div className={failed ? 'alert alert--error' : 'alert alert--success'}>{message}</div>
      ) : null}

      <div className="split">
        <div className="card table-wrap">
          {isPending ? <div className="skeleton" style={{ height: 200 }} /> : null}

          {data ? (
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Category</th>
                  <th className="num">Price</th>
                  <th>Available</th>
                  <th />
                </tr>
              </thead>

              <tbody>
                {data.products.map((product) => (
                  <tr key={product.id}>
                    <td style={{ fontWeight: 600 }}>{product.name}</td>
                    <td className="muted">{product.category?.name ?? '-'}</td>
                    <td className="num">{product.formattedPrice}</td>
                    <td>
                      <span className={product.is_available ? 'badge badge--good' : 'badge badge--bad'}>
                        {product.is_available ? 'Yes' : 'No'}
                      </span>
                    </td>
                    <td className="num">
                      <div className="row" style={{ justifyContent: 'flex-end' }}>
                        <button type="button" className="btn btn--sm" onClick={() => startEdit(product)}>
                          Edit
                        </button>
                        <button
                          type="button"
                          className="btn btn--sm btn--danger"
                          disabled={deleteProduct.isPending}
                          onClick={() => deleteProduct.mutate(product.id)}
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
          <h2>{editing ? `Edit ${editing.name}` : 'New product'}</h2>

          <div className="field">
            <label className="field__label" htmlFor="categoryId">
              Category
            </label>
            <select
              id="categoryId"
              value={form.categoryId}
              onChange={(event) => setForm({ ...form, categoryId: event.target.value })}
            >
              <option value="">Choose a category</option>
              {categories?.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
            {errors['category.id'] ? (
              <span className="field__error">{errors['category.id'][0]}</span>
            ) : null}
          </div>

          <Field
            label="Name"
            name="name"
            value={form.name}
            errors={errors.name}
            onChange={(event) => setForm({ ...form, name: event.target.value })}
          />

          <Field
            label="Price (EUR)"
            name="price"
            type="number"
            step="0.01"
            min="0"
            value={form.price}
            errors={errors.price}
            onChange={(event) => setForm({ ...form, price: event.target.value })}
          />

          <div className="field">
            <label className="field__label" htmlFor="description">
              Description
            </label>
            <textarea
              id="description"
              rows={3}
              value={form.description}
              onChange={(event) => setForm({ ...form, description: event.target.value })}
            />
          </div>

          <label className="row small" style={{ marginBottom: '0.9rem' }}>
            <input
              type="checkbox"
              style={{ width: 'auto' }}
              checked={form.is_available}
              onChange={(event) => setForm({ ...form, is_available: event.target.checked })}
            />
            Available to order
          </label>

          <button type="submit" className="btn btn--primary btn--block" disabled={saveProduct.isPending}>
            {saveProduct.isPending ? 'Saving...' : editing ? 'Save changes' : 'Create product'}
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
