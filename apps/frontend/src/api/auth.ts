import { api, setToken, clearToken } from '../lib/api'
import { User } from '../models/User'
import type { ApiPayload } from '../models/Model'

interface AuthResponse {
  user: ApiPayload
  token: string
}

export interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface LoginPayload {
  email: string
  password: string
}

export async function register(payload: RegisterPayload): Promise<User> {
  const { data } = await api.post<AuthResponse>('/auth/register', payload)

  setToken(data.token)

  return new User().hydrate(data.user)
}

export async function login(payload: LoginPayload): Promise<User> {
  const { data } = await api.post<AuthResponse>('/auth/login', payload)

  setToken(data.token)

  return new User().hydrate(data.user)
}

export async function logout(): Promise<void> {
  try {
    await api.post('/auth/logout')
  } finally {
    // The local token goes regardless. If the request failed because the token
    // was already invalid, the user still expects to be signed out.
    clearToken()
  }
}

export async function me(): Promise<User> {
  const { data } = await api.get<ApiPayload>('/auth/me')

  return new User().hydrate(data)
}
