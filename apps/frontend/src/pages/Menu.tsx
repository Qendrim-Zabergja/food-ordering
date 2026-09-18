import { useState } from 'react'
import { useAddToCart } from '../hooks/useCart'
import { useProductCategories, useProducts } from '../hooks/useProducts'
import { errorMessage } from '../lib/api'
import { ProductIncludes, type Product } from '../models/Product'

export function Menu() {
  const [search, setSearch] = useState('')
  const [categoryId, setCategoryId] = useState<string>('')
  const [feedback, setFeedback] = useState('')

  const { data: categories } = useProductCategories()
  const { data, isPending, isError, error } = useProducts({
    search: search || undefined,
    category: categoryId || undefined,
    is_available: true,
    with: [ProductIncludes.CATEGORY],
    sort: 'name',
    limit: 60,
  })

  const addToCart = useAddToCart()

  function add(product: Product) {
    setFeedback('')

    addToCart.mutate(
      { productId: product.id },
      {
        onSuccess: () => setFeedback(`${product.name} added to your cart.`),
        onError: (mutationError) => setFeedback(errorMessage(mutationError)),
      },
    )
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Menu</h1>
          <p className="muted small">
            {data ? `${data.total} item${data.total === 1 ? '' : 's'} available` : 'Loading the menu…'}
          </p>
        </div>
      </div>

      <div className="filters">
        <input
          className="filters__search"
          type="search"
          placeholder="Search the menu…"
          aria-label="Search the menu"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />

        <button
          type="button"
          className={categoryId === '' ? 'chip chip--active' : 'chip'}
          onClick={() => setCategoryId('')}
        >
          All
        </button>

        {categories?.map((category) => (
          <button
            key={category.id}
            type="button"
            className={categoryId === category.id ? 'chip chip--active' : 'chip'}
            onClick={() => setCategoryId(category.id)}
          >
            {category.name}
          </button>
        ))}
      </div>

      {feedback ? <div className="alert alert--success">{feedback}</div> : null}

      {isError ? <div className="alert alert--error">{errorMessage(error)}</div> : null}

      {isPending ? (
        <div className="grid">
          {Array.from({ length: 8 }, (_, index) => (
            <div key={index} className="skeleton" style={{ height: 250 }} />
          ))}
        </div>
      ) : null}

      {data && data.products.length === 0 ? (
        <div className="state">
          <h2>Nothing matches</h2>
          <p>Try a different search or category.</p>
        </div>
      ) : null}

      <div className="grid">
        {data?.products.map((product) => (
          <article key={product.id} className="product">
            {product.image_url ? (
              <div className="product__media">
                <img
                  src={product.image_url}
                  /* The name is already in the heading below, so the photo is
                     decorative and an empty alt keeps a screen reader from
                     reading every dish twice. */
                  alt=""
                  loading="lazy"
                  decoding="async"
                  width={600}
                  height={400}
                />
              </div>
            ) : null}

            <div className="product__body">
              <span className="product__name">{product.name}</span>

              {product.category ? (
                <span className="small muted">{product.category.name}</span>
              ) : null}

              {product.description ? (
                <p className="small muted" style={{ margin: 0 }}>
                  {product.description}
                </p>
              ) : null}

              <div className="product__foot">
                <span className="product__price">{product.formattedPrice}</span>

                <button
                  type="button"
                  className="btn btn--primary btn--sm"
                  disabled={addToCart.isPending}
                  onClick={() => add(product)}
                >
                  Add
                </button>
              </div>
            </div>
          </article>
        ))}
      </div>
    </>
  )
}
