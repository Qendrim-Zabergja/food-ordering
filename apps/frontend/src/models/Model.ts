export type ApiPayload = Record<string, unknown>

/**
 * Base class for everything that comes back from the API.
 *
 * A raw response object is never passed around the application: it is hydrated
 * into a class first. That gives one place to map an API field onto a property,
 * one place to turn nested payloads into their own models, and somewhere for
 * derived getters to live.
 *
 *     const product = new Product().hydrate(response.data)
 *     const products = Product.collection(response.data.data)
 *
 * Note `id` is always the uuid: the API exposes the uuid under that name and
 * never sends its internal numeric id.
 */
export abstract class Model {
  id = ''
  created_at: string | null = null
  updated_at: string | null = null

  hydrate(data: ApiPayload): this {
    Object.assign(this, data)

    return this
  }

  /**
   * Hydrates a list. Tolerates undefined so a caller can pass a response body
   * that has not arrived yet without guarding first.
   */
  static collection<T extends Model>(this: new () => T, data: ApiPayload[] | undefined | null): T[] {
    return data?.map((item) => new this().hydrate(item)) ?? []
  }
}

/** Formats a price that the API sent in cents. */
export function formatPrice(cents: number | null | undefined): string {
  return new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'EUR',
  }).format((cents ?? 0) / 100)
}
