import axios, { AxiosError } from 'axios'

const TOKEN_KEY = 'food-ordering.token'

/**
 * The bearer token lives in localStorage. Every read and write is guarded:
 * storage throws in a private window and in some embedded browsers, and a
 * signed-out app is a much better outcome than a blank screen.
 */
export function getToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setToken(token: string): void {
  try {
    localStorage.setItem(TOKEN_KEY, token)
  } catch {
    // Non-persistent session. The app still works until the tab is closed.
  }
}

export function clearToken(): void {
  try {
    localStorage.removeItem(TOKEN_KEY)
  } catch {
    // Nothing to clean up.
  }
}

/**
 * The single axios instance every request goes through, so the token and the
 * 401 handling are defined in exactly one place.
 */
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = getToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

/** Fired when the API rejects our token, so the auth context can sign out. */
export const UNAUTHENTICATED_EVENT = 'auth:unauthenticated'

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      clearToken()
      window.dispatchEvent(new Event(UNAUTHENTICATED_EVENT))
    }

    return Promise.reject(error)
  },
)

interface LaravelErrorBody {
  message?: string
  errors?: Record<string, string[]>
}

/**
 * Laravel returns 422 with { message, errors: { field: [messages] } }.
 * Forms read this to put each message next to its own input.
 */
export function validationErrors(error: unknown): Record<string, string[]> {
  if (error instanceof AxiosError && error.response?.status === 422) {
    return (error.response.data as LaravelErrorBody).errors ?? {}
  }

  return {}
}

/** The first message worth showing a person, whatever went wrong. */
export function errorMessage(error: unknown, fallback = 'Something went wrong.'): string {
  if (error instanceof AxiosError) {
    const body = error.response?.data as LaravelErrorBody | undefined
    const firstFieldError = Object.values(body?.errors ?? {})[0]?.[0]

    return firstFieldError ?? body?.message ?? error.message ?? fallback
  }

  return fallback
}

/** Shape of a Laravel paginated resource collection. */
export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
